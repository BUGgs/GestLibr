#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

ENV_FILE="${1:-${PROJECT_ROOT}/.env.prod}"
MIGRATION_DIR="${2:-${PROJECT_ROOT}/docker/mysql/migrations}"

if [[ ! -f "${ENV_FILE}" ]]; then
    echo "Env file not found: ${ENV_FILE}" >&2
    exit 1
fi

if [[ ! -d "${MIGRATION_DIR}" ]]; then
    echo "Migration directory not found: ${MIGRATION_DIR}" >&2
    exit 1
fi

COMPOSE_ARGS=(--env-file "${ENV_FILE}" -f compose.yaml -f compose.prod.yaml)

dc() {
    docker compose \
        --env-file "${ENV_FILE}" \
        -f "${PROJECT_ROOT}/compose.yaml" \
        -f "${PROJECT_ROOT}/compose.prod.yaml" \
        "$@"
}

MYSQL_AUTH_MODE=""
MYSQL_AUTH_DESC=""

detect_mysql_auth() {
    if dc exec -T mysql sh -lc 'mysql -N -B -h127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "SELECT 1"' >/dev/null 2>&1; then
        MYSQL_AUTH_MODE="root"
        MYSQL_AUTH_DESC="root"
        return 0
    fi

    if dc exec -T mysql sh -lc 'mysql -N -B -h127.0.0.1 -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SELECT 1"' >/dev/null 2>&1; then
        MYSQL_AUTH_MODE="app"
        MYSQL_AUTH_DESC="\$MYSQL_USER"
        return 0
    fi

    echo "Unable to authenticate to MySQL with root or app credentials from env file." >&2
    return 1
}

mysql_stdin() {
    if [[ "${MYSQL_AUTH_MODE}" == "root" ]]; then
        dc exec -T mysql sh -lc 'mysql -h127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
    else
        dc exec -T mysql sh -lc 'mysql -h127.0.0.1 -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
    fi
}

mysql_query() {
    local sql="$1"
    if [[ "${MYSQL_AUTH_MODE}" == "root" ]]; then
        dc exec -T mysql sh -lc 'mysql -N -B -h127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' <<<"${sql}"
    else
        dc exec -T mysql sh -lc 'mysql -N -B -h127.0.0.1 -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' <<<"${sql}"
    fi
}

sha256_file() {
    local file="$1"
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "${file}" | awk '{print $1}'
    else
        shasum -a 256 "${file}" | awk '{print $1}'
    fi
}

echo "==> Ensuring schema_migrations table exists"
detect_mysql_auth
echo "==> Using MySQL auth: ${MYSQL_AUTH_DESC}"
mysql_query "CREATE TABLE IF NOT EXISTS schema_migrations (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(255) NOT NULL, checksum VARCHAR(64) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_schema_migrations_filename (filename)) ENGINE=InnoDB DEFAULT CHARSET=utf8;"

shopt -s nullglob
migrations=("${MIGRATION_DIR}"/*.sql)
if [[ ${#migrations[@]} -eq 0 ]]; then
    echo "==> No migration files found in ${MIGRATION_DIR}"
    exit 0
fi

applied_count=0
for file in "${migrations[@]}"; do
    filename="$(basename "${file}")"
    filename_sql="$(printf "%s" "${filename}" | sed "s/'/''/g")"

    already_applied="$(mysql_query "SELECT COUNT(*) FROM schema_migrations WHERE filename='${filename_sql}';")"
    if [[ "${already_applied}" != "0" ]]; then
        echo "==> Skip ${filename} (already applied)"
        continue
    fi

    echo "==> Applying ${filename}"
    mysql_stdin < "${file}"

    checksum="$(sha256_file "${file}")"
    mysql_query "INSERT INTO schema_migrations (filename, checksum) VALUES ('${filename_sql}', '${checksum}');"
    applied_count=$((applied_count + 1))
done

echo "==> Migrations completed. Applied: ${applied_count}"
