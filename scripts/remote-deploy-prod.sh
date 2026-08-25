#!/usr/bin/env bash
set -euo pipefail

usage() {
    cat <<'EOF'
Usage: scripts/remote-deploy-prod.sh [options]

Options:
  --target-dir <path>            Remote base dir (default: /srv/gestlibr)
  --project-name <name>          Compose project name (default: gestlibr-prod)
  --proxy-network <name>         Existing Docker network shared with Caddy (default: proxy)
  --env-file <path>              Remote env file (default: /srv/gestlibr/env/.env.prod)
  -h, --help                     Show help
EOF
}

TARGET_DIR="/srv/gestlibr"
PROJECT_NAME="gestlibr-prod"
PROXY_NETWORK="proxy"
ENV_FILE=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --target-dir) TARGET_DIR="${2:?}"; shift 2 ;;
        --project-name) PROJECT_NAME="${2:?}"; shift 2 ;;
        --proxy-network) PROXY_NETWORK="${2:?}"; shift 2 ;;
        --env-file) ENV_FILE="${2:?}"; shift 2 ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage; exit 1 ;;
    esac
done

DEPLOY_DIR="${TARGET_DIR}/deploy"
WEB_DIR="${TARGET_DIR}/web"
MYSQL_DIR="${TARGET_DIR}/mysql"
BACKUP_DIR="${TARGET_DIR}/backups/mysql"
ENV_DIR="${TARGET_DIR}/env"
if [[ -z "${ENV_FILE}" ]]; then
    ENV_FILE="${ENV_DIR}/.env.prod"
fi

if [[ ! -d "${DEPLOY_DIR}" ]]; then
    echo "Deploy directory not found: ${DEPLOY_DIR}" >&2
    exit 1
fi

if [[ ! -f "${DEPLOY_DIR}/compose.yaml" || ! -f "${DEPLOY_DIR}/compose.prod.yaml" ]]; then
    echo "compose.yaml / compose.prod.yaml missing in ${DEPLOY_DIR}" >&2
    exit 1
fi

mkdir -p "${MYSQL_DIR}" "${BACKUP_DIR}" "${ENV_DIR}" "${WEB_DIR}/pictures"
chmod 700 "${MYSQL_DIR}" || true

set_env_value() {
    local key="$1"
    local value="$2"
    local file="$3"
    local escaped
    escaped="$(printf '%s' "${value}" | sed -e 's/[\/&|]/\\&/g')"
    if grep -qE "^${key}=" "${file}"; then
        sed -i.bak -E "s|^${key}=.*$|${key}=${escaped}|g" "${file}"
        rm -f "${file}.bak"
    else
        printf '%s=%s\n' "${key}" "${value}" >> "${file}"
    fi
}

generate_secret() {
    openssl rand -base64 48 | tr -d '\n' | tr '/+=' 'abc' | cut -c1-40
}

if [[ ! -f "${ENV_FILE}" ]]; then
    if [[ -f "${DEPLOY_DIR}/.env.prod.example" ]]; then
        cp "${DEPLOY_DIR}/.env.prod.example" "${ENV_FILE}"
    elif [[ -f "${DEPLOY_DIR}/.env.prod" ]]; then
        cp "${DEPLOY_DIR}/.env.prod" "${ENV_FILE}"
    else
        cat > "${ENV_FILE}" <<EOF
COMPOSE_PROJECT_NAME=${PROJECT_NAME}
TZ=Europe/Paris
MYSQL_DATABASE=gestlibr
MYSQL_USER=gestlibr
EOF
    fi
fi
chmod 600 "${ENV_FILE}"

set_env_value "COMPOSE_PROJECT_NAME" "${PROJECT_NAME}" "${ENV_FILE}"
set_env_value "DEPLOY_BASE_DIR" "${TARGET_DIR}" "${ENV_FILE}"
set_env_value "PROXY_NETWORK" "${PROXY_NETWORK}" "${ENV_FILE}"

current_db_name="$(grep '^MYSQL_DATABASE=' "${ENV_FILE}" | head -n1 | cut -d= -f2- || true)"
if [[ -z "${current_db_name}" ]]; then
    set_env_value "MYSQL_DATABASE" "gestlibr" "${ENV_FILE}"
fi

current_db_user="$(grep '^MYSQL_USER=' "${ENV_FILE}" | head -n1 | cut -d= -f2- || true)"
if [[ -z "${current_db_user}" ]]; then
    set_env_value "MYSQL_USER" "gestlibr" "${ENV_FILE}"
fi

current_root_pass="$(grep '^MYSQL_ROOT_PASSWORD=' "${ENV_FILE}" | head -n1 | cut -d= -f2- || true)"
if [[ -z "${current_root_pass}" || "${current_root_pass}" == CHANGE_ME* ]]; then
    set_env_value "MYSQL_ROOT_PASSWORD" "$(generate_secret)" "${ENV_FILE}"
fi

current_app_pass="$(grep '^MYSQL_PASSWORD=' "${ENV_FILE}" | head -n1 | cut -d= -f2- || true)"
if [[ -z "${current_app_pass}" || "${current_app_pass}" == CHANGE_ME* ]]; then
    set_env_value "MYSQL_PASSWORD" "$(generate_secret)" "${ENV_FILE}"
fi

if ! docker network inspect "${PROXY_NETWORK}" >/dev/null 2>&1; then
    echo "Required external proxy network not found: ${PROXY_NETWORK}" >&2
    exit 1
fi

dc() {
    docker compose --env-file "${ENV_FILE}" -f "${DEPLOY_DIR}/compose.yaml" -f "${DEPLOY_DIR}/compose.prod.yaml" "$@"
}

echo "==> Starting MySQL"
dc up -d mysql >/dev/null

mysql_cid="$(dc ps -q mysql)"
if [[ -z "${mysql_cid}" ]]; then
    echo "MySQL container not found after startup." >&2
    exit 1
fi

echo "==> Waiting for MySQL health"
for _ in $(seq 1 90); do
    health="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "${mysql_cid}")"
    if [[ "${health}" == "healthy" || "${health}" == "running" ]]; then
        break
    fi
    sleep 2
done

health="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "${mysql_cid}")"
if [[ "${health}" != "healthy" && "${health}" != "running" ]]; then
    echo "MySQL is not healthy (${health})." >&2
    exit 1
fi

timestamp="$(date +%Y%m%d-%H%M%S)"
backup_file="${BACKUP_DIR}/gestlibr-${timestamp}.sql.gz"
echo "==> Creating DB backup: ${backup_file}"
if dc exec -T mysql sh -lc 'mysql -N -B -h127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SELECT 1"' >/dev/null 2>&1; then
    db_dump_cmd='mysqldump -h127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
elif dc exec -T mysql sh -lc 'mysql -N -B -h127.0.0.1 -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SELECT 1"' >/dev/null 2>&1; then
    echo "==> Root auth unavailable; using application DB user for backup/migrations."
    db_dump_cmd='mysqldump -h127.0.0.1 -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
else
    echo "Unable to authenticate to MySQL for backup (root and app credentials failed)." >&2
    exit 1
fi
dc exec -T mysql sh -lc "${db_dump_cmd}" | gzip -c > "${backup_file}"

echo "==> Running incremental migrations"
bash "${DEPLOY_DIR}/scripts/run-db-migrations.sh" "${ENV_FILE}" "${DEPLOY_DIR}/docker/mysql/migrations"

echo "==> Starting web service"
dc up -d web >/dev/null

echo "==> Services status"
dc ps

echo "==> Remote deployment apply completed."
