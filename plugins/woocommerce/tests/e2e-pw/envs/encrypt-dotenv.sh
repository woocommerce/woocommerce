if [ -z "${1}" ]; then
	echo "ERROR: Missing argument"
	echo "usage: $0 path/to/env"
	exit 1
fi

SCRIPT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")" || return
  pwd -P
)

openssl enc -aes-256-cbc -iter 1000 -pass env:E2E_ENV_KEY -in "$SCRIPT_PATH"/../.env -out "$SCRIPT_PATH"/"${1}"/.env.enc
