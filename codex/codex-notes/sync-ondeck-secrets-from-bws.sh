#!/usr/bin/env bash
set -euo pipefail

TOKEN_ENV="/etc/pirate-secrets-manager/bws.env"
OUTPUT_DIR="/etc/pirate-secrets-manager/rendered"
BWS_BIN="/usr/local/bin/bws"

ONDECK_ENV="${OUTPUT_DIR}/ondeck.env"
MARY_ENV="${OUTPUT_DIR}/mary.env"

ONDECK_REQUIRED_KEYS=(
  UNIPILE_API_KEY
  UNIPILE_ACCOUNT_ID
  SERPER_API_KEY
  ONDECK_API_TOKEN
)

ONDECK_OPTIONAL_KEYS=(
  SMTP_ACCOUNTS_JSON
)

MARY_REQUIRED_KEYS=(
  ONDECK_BASE_URL
  ONDECK_API_TOKEN
)

usage() {
  cat <<'USAGE'
Usage: sync-ondeck-secrets-from-bws.sh [--check-only]

Fetches whitelisted Mary/OnDeck secrets from Bitwarden Secrets Manager and
renders root-readable env files:
  /etc/pirate-secrets-manager/rendered/ondeck.env
  /etc/pirate-secrets-manager/rendered/mary.env

Never prints secret values. Prints only presence, length, and sha256 fingerprint.

Note: this script renders env files. Coolify/container wiring is a separate
step unless the deployment reads these files.
USAGE
}

CHECK_ONLY=0
case "${1:-}" in
  "")
    ;;
  "--check-only")
    CHECK_ONLY=1
    ;;
  "-h"|"--help")
    usage
    exit 0
    ;;
  *)
    usage >&2
    exit 2
    ;;
esac

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || fail "missing command: $1"
}

fingerprint() {
  local value="$1"
  local length hash
  length=$(printf '%s' "$value" | wc -c | tr -d ' ')
  hash=$(printf '%s' "$value" | sha256sum | awk '{print substr($1, 1, 12)}')
  printf 'len=%s sha256=%s' "$length" "$hash"
}

shell_quote_value() {
  local value="$1"
  printf "'%s'" "$(printf '%s' "$value" | sed "s/'/'\\\\''/g")"
}

get_secret_value() {
  local key="$1"
  jq -r --arg key "$key" '
    map(select(.key == $key)) |
    if length == 0 then "" else .[0].value end
  ' <<<"$SECRETS_JSON"
}

render_env_file() {
  local target="$1"
  local profile="$2"
  local tmp="${target}.tmp"
  local missing=0

  : > "$tmp"
  chmod 600 "$tmp"

  local value
  local required_keys=()
  local optional_keys=()

  case "$profile" in
    ondeck)
      required_keys=("${ONDECK_REQUIRED_KEYS[@]}")
      optional_keys=("${ONDECK_OPTIONAL_KEYS[@]}")
      ;;
    mary)
      required_keys=("${MARY_REQUIRED_KEYS[@]}")
      ;;
    *)
      fail "unknown render profile: $profile"
      ;;
  esac

  for key in "${required_keys[@]}"; do
    value=$(get_secret_value "$key")
    if [[ -z "$value" ]]; then
      printf 'missing\t%s\n' "$key"
      missing=$((missing + 1))
      continue
    fi
    printf 'present\t%s\t%s\n' "$key" "$(fingerprint "$value")"
    printf '%s=%s\n' "$key" "$(shell_quote_value "$value")" >> "$tmp"
  done

  if [[ "$profile" == "ondeck" ]]; then
    local twenty_value twenty_key
    twenty_value=$(get_secret_value "TWENTY_API_KEY")
    twenty_key="TWENTY_API_KEY"
    if [[ -z "$twenty_value" ]]; then
      twenty_value=$(get_secret_value "TWENTY_API")
      twenty_key="TWENTY_API"
    fi
    if [[ -z "$twenty_value" ]]; then
      printf 'missing\t%s\n' "TWENTY_API_KEY|TWENTY_API"
      missing=$((missing + 1))
    else
      printf 'present\t%s\t%s\n' "$twenty_key" "$(fingerprint "$twenty_value")"
      printf '%s=%s\n' "$twenty_key" "$(shell_quote_value "$twenty_value")" >> "$tmp"
    fi
  fi

  for key in "${optional_keys[@]}"; do
    value=$(get_secret_value "$key")
    if [[ -z "$value" ]]; then
      printf 'optional-missing\t%s\n' "$key"
      continue
    fi
    printf 'present\t%s\t%s\n' "$key" "$(fingerprint "$value")"
    printf '%s=%s\n' "$key" "$(shell_quote_value "$value")" >> "$tmp"
  done

  if [[ "$target" == "$ONDECK_ENV" ]]; then
    printf '%s=%s\n' "UNIPILE_DSN" "'https://api26.unipile.com:15639'" >> "$tmp"
    printf 'config\t%s\t%s\n' "UNIPILE_DSN" "https://api26.unipile.com:15639"
  fi

  if [[ "$missing" -gt 0 ]]; then
    rm -f "$tmp"
    return "$missing"
  fi

  if [[ "$CHECK_ONLY" -eq 1 ]]; then
    rm -f "$tmp"
    return "$missing"
  fi

  if [[ -f "$target" ]] && cmp -s "$tmp" "$target"; then
    rm -f "$tmp"
    printf 'unchanged\t%s\n' "$target"
  else
    install -o root -g root -m 0600 "$tmp" "$target"
    rm -f "$tmp"
    printf 'updated\t%s\n' "$target"
  fi

  return "$missing"
}

container_env_fingerprint() {
  local container="$1"
  local key="$2"
  docker inspect "$container" --format '{{range .Config.Env}}{{println .}}{{end}}' 2>/dev/null |
    awk -F= -v wanted="$key" '$1 == wanted {sub(/^[^=]*=/, ""); print; found=1} END {if (!found) exit 1}' |
    while IFS= read -r value; do
      fingerprint "$value"
    done
}

compare_container() {
  local label="$1"
  local container="$2"
  shift 2
  local keys=("$@")

  docker inspect "$container" >/dev/null 2>&1 || {
    printf 'container-missing\t%s\t%s\n' "$label" "$container"
    return 0
  }

  printf 'container\t%s\t%s\n' "$label" "$container"
  for key in "${keys[@]}"; do
    value=$(get_secret_value "$key")
    [[ -n "$value" ]] || continue
    bws_fp=$(fingerprint "$value")
    if docker_fp=$(container_env_fingerprint "$container" "$key"); then
      if [[ "$docker_fp" == "$bws_fp" ]]; then
        printf 'container-match\t%s\t%s\t%s\n' "$label" "$key" "$docker_fp"
      else
        printf 'container-diff\t%s\t%s\tbws:%s docker:%s\n' "$label" "$key" "$bws_fp" "$docker_fp"
      fi
    else
      printf 'container-env-missing\t%s\t%s\n' "$label" "$key"
    fi
  done
}

[[ "$EUID" -eq 0 ]] || fail "run as root"
[[ -r "$TOKEN_ENV" ]] || fail "cannot read $TOKEN_ENV"
need_cmd "$BWS_BIN"
need_cmd jq
need_cmd sha256sum
need_cmd docker

set -a
# shellcheck source=/dev/null
. "$TOKEN_ENV"
set +a

[[ -n "${BWS_ACCESS_TOKEN:-}" ]] || fail "BWS_ACCESS_TOKEN is not set in $TOKEN_ENV"

mkdir -p "$OUTPUT_DIR"
chmod 700 /etc/pirate-secrets-manager "$OUTPUT_DIR"

SECRETS_JSON=$("$BWS_BIN" secret list --output json)
secret_count=$(jq 'length' <<<"$SECRETS_JSON")
project_count=$("$BWS_BIN" project list --output json | jq 'length')
printf 'bws-projects\t%s\n' "$project_count"
printf 'bws-secrets\t%s\n' "$secret_count"

missing_total=0

printf 'render-target\t%s\n' "$ONDECK_ENV"
if ! render_env_file "$ONDECK_ENV" "ondeck"; then
  missing_total=$((missing_total + 1))
fi

printf 'render-target\t%s\n' "$MARY_ENV"
if ! render_env_file "$MARY_ENV" "mary"; then
  missing_total=$((missing_total + 1))
fi

compare_container "mary-gateway" "mary-gateway" "${MARY_REQUIRED_KEYS[@]}"
compare_container "mary-dashboard" "mary-dashboard" "${MARY_REQUIRED_KEYS[@]}"
ondeck_container=$(docker ps --format '{{.Names}}' | while read -r c; do
  docker inspect "$c" --format '{{range .Config.Env}}{{println .}}{{end}}' 2>/dev/null |
    grep -q '^COOLIFY_FQDN=ondeck.internals.pirate.builders$' && { printf '%s\n' "$c"; break; }
done)
[[ -n "$ondeck_container" ]] && compare_container "ondeck" "$ondeck_container" "${ONDECK_REQUIRED_KEYS[@]}" TWENTY_API_KEY TWENTY_API "${ONDECK_OPTIONAL_KEYS[@]}"

if [[ "$missing_total" -gt 0 ]]; then
  fail "one or more whitelisted secrets are missing; check Bitwarden machine account project access"
fi
