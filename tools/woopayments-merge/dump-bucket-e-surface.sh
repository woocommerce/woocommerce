#!/usr/bin/env bash
#
# Dump the Bucket-E payment surface for the given order IDs from a store, as
# line-delimited JSON (WooPayments → core merge, A0 verification harness primitive).
#
# The WP runner is injected via $WP so this works against any store without baking in
# a transport. The script is piped over stdin (eval-file -) so it does not need to exist
# inside a container's filesystem.
#
#   WP="docker exec -i wcpay_wp_default wp"  dump-bucket-e-surface.sh 12 34   # reference store
#   WP="pnpm --silent wp"                    dump-bucket-e-surface.sh 12 34   # wp-env / local
#
# Compose two runs into a parity diff:
#   diff <(WP="$REF_WP" dump-bucket-e-surface.sh $IDS) <(WP="$TARGET_WP" dump-bucket-e-surface.sh $IDS)

set -uo pipefail

SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP="${WP:-wp}"

if [ "$#" -eq 0 ]; then
	echo "usage: WP='<wp runner>' $0 <order_id> [<order_id>...]" >&2
	exit 2
fi

# shellcheck disable=SC2086
$WP eval-file - "$@" < "$SELF_DIR/dump-bucket-e-surface.php"
