#!/bin/bash
# Put a set of built assets in place. Usage: swap.sh control | rolldown
set -e
A=/home/user/workspace/repo/plugins/woocommerce/assets/client
case "$1" in
  control) B=/home/user/wc-bench/control/blocks; AD=/home/user/wc-bench/control/admin ;;
  rolldown) B=/tmp/wc-out; AD=/tmp/wc-admin ;;
  *) echo "control or rolldown"; exit 1 ;;
esac
rm -rf "$A/blocks" "$A/admin"
cp -r "$B" "$A/blocks"
cp -r "$AD" "$A/admin"
cd /home/user/store && wp cache flush >/dev/null && wp transient delete --all >/dev/null
echo "$1 in place: $(find $A/blocks -type f | wc -l) blocks files, $(find $A/admin -type f | wc -l) admin files"
