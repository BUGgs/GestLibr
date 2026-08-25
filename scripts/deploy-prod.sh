#!/usr/bin/env bash
set -euo pipefail

usage() {
    cat <<'EOF'
Usage: scripts/deploy-prod.sh [options]

Options:
  --host <host>                  SSH host (default: manager.wiroo.com)
  --user <user>                  SSH user (default: ubuntu)
  --target-dir <path>            Remote base dir (default: /srv/gestlibr)
  --project-name <name>          Compose project name (default: gestlibr-prod)
  --proxy-network <name>         Existing Docker network shared with Caddy (default: proxy)
  --env-file-local <path>        Optional local .env file to push as remote env
  --sync-mysql-secrets           Also push MySQL secrets from local env (default: preserve remote MySQL secrets)
  --dry-run                      Show actions without applying remote deployment
  -h, --help                     Show this help
EOF
}

HOST="manager.wiroo.com"
SSH_USER="ubuntu"
TARGET_DIR="/srv/gestlibr"
PROJECT_NAME="gestlibr-prod"
PROXY_NETWORK="proxy"
ENV_FILE_LOCAL=""
SYNC_MYSQL_SECRETS=0
DRY_RUN=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        --host) HOST="${2:?}"; shift 2 ;;
        --user) SSH_USER="${2:?}"; shift 2 ;;
        --target-dir) TARGET_DIR="${2:?}"; shift 2 ;;
        --project-name) PROJECT_NAME="${2:?}"; shift 2 ;;
        --proxy-network) PROXY_NETWORK="${2:?}"; shift 2 ;;
        --env-file-local) ENV_FILE_LOCAL="${2:?}"; shift 2 ;;
        --sync-mysql-secrets) SYNC_MYSQL_SECRETS=1; shift ;;
        --dry-run) DRY_RUN=1; shift ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage; exit 1 ;;
    esac
done

if [[ "${HOST}" != "manager.wiroo.com" ]]; then
    echo "Refusing production deployment to unexpected host: ${HOST}" >&2
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

if [[ -z "${ENV_FILE_LOCAL}" && -f "${PROJECT_ROOT}/.env.prod" ]]; then
    ENV_FILE_LOCAL="${PROJECT_ROOT}/.env.prod"
fi

REMOTE_DEPLOY_DIR="${TARGET_DIR}/deploy"
REMOTE_WEB_DIR="${TARGET_DIR}/web"
REMOTE_ENV_DIR="${TARGET_DIR}/env"
REMOTE_ENV_FILE="${REMOTE_ENV_DIR}/.env.prod"
REMOTE_BACKUP_DIR="${TARGET_DIR}/backups/mysql"
SSH_TARGET="${SSH_USER}@${HOST}"

set_env_value() {
    local key="$1"
    local value="$2"
    local file="$3"
    local escaped
    escaped="$(printf '%s' "${value}" | sed -e 's/[\\/&|]/\\&/g')"
    if grep -qE "^${key}=" "${file}"; then
        sed -i.bak -E "s|^${key}=.*$|${key}=${escaped}|g" "${file}"
        rm -f "${file}.bak"
    else
        printf '%s=%s\n' "${key}" "${value}" >> "${file}"
    fi
}

echo "==> Deploy target: ${SSH_TARGET}"
echo "==> Base directory: ${TARGET_DIR}"
if [[ -n "${ENV_FILE_LOCAL}" ]]; then
    echo "==> Env source: ${ENV_FILE_LOCAL}"
    if [[ "${SYNC_MYSQL_SECRETS}" -eq 1 ]]; then
        echo "==> MySQL secrets sync: enabled (local values will be applied)"
    else
        echo "==> MySQL secrets sync: disabled (remote values preserved)"
    fi
else
    echo "==> Env source: remote existing file (no local .env.prod provided)"
fi

if [[ -n "${ENV_FILE_LOCAL}" && ! -f "${ENV_FILE_LOCAL}" ]]; then
    echo "Local env file not found: ${ENV_FILE_LOCAL}" >&2
    exit 1
fi

ssh "${SSH_TARGET}" "echo 'SSH OK on ' \$(hostname)"

echo "==> Preparing remote directories"
ssh "${SSH_TARGET}" "sudo mkdir -p '${REMOTE_DEPLOY_DIR}' '${REMOTE_WEB_DIR}' '${TARGET_DIR}/mysql' '${REMOTE_BACKUP_DIR}' '${REMOTE_ENV_DIR}' && sudo chown '${SSH_USER}:${SSH_USER}' '${REMOTE_DEPLOY_DIR}' '${REMOTE_WEB_DIR}' '${REMOTE_BACKUP_DIR}' '${REMOTE_ENV_DIR}'"

echo "==> Normalizing remote web layout (legacy /web/web cleanup)"
ssh "${SSH_TARGET}" "set -e; \
  if [ -d '${REMOTE_WEB_DIR}/web' ]; then \
    mkdir -p '${REMOTE_WEB_DIR}/pictures'; \
    if [ -d '${REMOTE_WEB_DIR}/web/pictures' ]; then \
      rsync -r --ignore-existing '${REMOTE_WEB_DIR}/web/pictures/' '${REMOTE_WEB_DIR}/pictures/' >/dev/null 2>&1 || true; \
    fi; \
    rm -rf '${REMOTE_WEB_DIR}/web'; \
  fi"

echo "==> Syncing deployment assets to ${REMOTE_DEPLOY_DIR}"
rsync -az --delete \
    --exclude='.nfs*' \
    --filter='P .nfs*' \
    --exclude='.git/' \
    --exclude='.DS_Store' \
    --exclude='.env' \
    --exclude='.env.prod' \
    --exclude='.env.dev' \
    --exclude='.env.dev.local' \
    --exclude='docker/mysql/data/' \
    --exclude='web/pictures/***' \
    "${PROJECT_ROOT}/" "${SSH_TARGET}:${REMOTE_DEPLOY_DIR}/"

echo "==> Syncing web runtime files to ${REMOTE_WEB_DIR} (pictures preserved)"
rsync -az --delete \
    --exclude='.nfs*' \
    --filter='P .nfs*' \
    --exclude='pictures/***' \
    "${PROJECT_ROOT}/web/" "${SSH_TARGET}:${REMOTE_WEB_DIR}/"

if [[ -n "${ENV_FILE_LOCAL}" ]]; then
    TMP_ENV_LOCAL="$(mktemp)"
    cp "${ENV_FILE_LOCAL}" "${TMP_ENV_LOCAL}"

    if [[ "${SYNC_MYSQL_SECRETS}" -eq 0 ]] && ssh "${SSH_TARGET}" "sudo test -f '${REMOTE_ENV_FILE}'"; then
        TMP_REMOTE_ENV="$(mktemp)"
        ssh "${SSH_TARGET}" "sudo cat '${REMOTE_ENV_FILE}'" > "${TMP_REMOTE_ENV}"
        for key in MYSQL_DATABASE MYSQL_USER MYSQL_PASSWORD MYSQL_ROOT_PASSWORD; do
            remote_value="$(grep -E "^${key}=" "${TMP_REMOTE_ENV}" | head -n1 | cut -d= -f2- || true)"
            if [[ -n "${remote_value}" ]]; then
                set_env_value "${key}" "${remote_value}" "${TMP_ENV_LOCAL}"
            fi
        done
        rm -f "${TMP_REMOTE_ENV}"
    fi

    TMP_REMOTE="/tmp/gestlibr-env-$$.prod"
    echo "==> Uploading explicit env file ${ENV_FILE_LOCAL}"
    rsync -az "${TMP_ENV_LOCAL}" "${SSH_TARGET}:${TMP_REMOTE}"
    ssh "${SSH_TARGET}" "sudo install -m 600 '${TMP_REMOTE}' '${REMOTE_ENV_FILE}' && rm -f '${TMP_REMOTE}'"
    rm -f "${TMP_ENV_LOCAL}"
fi

if [[ "${DRY_RUN}" -eq 1 ]]; then
    echo "==> Dry run enabled, skipping remote apply."
    exit 0
fi

echo "==> Running remote deployment apply script"
ssh "${SSH_TARGET}" "sudo bash '${REMOTE_DEPLOY_DIR}/scripts/remote-deploy-prod.sh' \
    --target-dir '${TARGET_DIR}' \
    --project-name '${PROJECT_NAME}' \
    --proxy-network '${PROXY_NETWORK}' \
    --env-file '${REMOTE_ENV_FILE}'"

echo "==> Deployment completed."
