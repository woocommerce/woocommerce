#!/bin/bash
# Alternating build benchmark. Usage: bench.sh <rounds> <out.jsonl>
ROUNDS=${1:-5}; OUT=${2:-/home/user/wc-bench/bench.jsonl}
REPO=/home/user/workspace/repo
B=$REPO/plugins/woocommerce/client/blocks; A=$REPO/plugins/woocommerce/client/admin
P=$REPO/tools/vite-proto
ASSETS=$REPO/plugins/woocommerce/assets/client
cold() {
  rm -rf $B/node_modules/.cache/webpack-* $REPO/node_modules/.cache/babel-loader $A/node_modules/.cache/webpack-* $B/node_modules/.cache/babel-loader $A/node_modules/.cache/babel-loader
}
count() { echo "{\"label\":\"$1\",\"files\":$(find $2 -type f | wc -l),\"js\":$(find $2 -name '*.js' | wc -l),\"css\":$(find $2 -name '*.css' | wc -l),\"bytes\":$(du -sb $2 | cut -f1)}" >> $OUT.files; }
for r in $(seq 1 $ROUNDS); do
  cold
  rm -rf $ASSETS/blocks; python3 $P/run.py webpack-cold-blocks $OUT $B 'pnpm run build:project:bundle'; count webpack-cold-blocks $ASSETS/blocks
  rm -rf /tmp/bench-blocks; python3 $P/run.py rolldown-blocks $OUT $P 'node build-blocks.mjs --out=/tmp/bench-blocks'; count rolldown-blocks /tmp/bench-blocks
  rm -rf $ASSETS/blocks; python3 $P/run.py webpack-warm-blocks $OUT $B 'pnpm run build:project:bundle'; count webpack-warm-blocks $ASSETS/blocks
  rm -rf $ASSETS/admin; python3 $P/run.py webpack-cold-admin $OUT $A 'pnpm run build:project:bundle'; count webpack-cold-admin $ASSETS/admin
  rm -rf /tmp/bench-admin; python3 $P/run.py rolldown-admin $OUT $P 'node build-admin.mjs --out=/tmp/bench-admin'; count rolldown-admin /tmp/bench-admin
  rm -rf $ASSETS/admin; python3 $P/run.py webpack-warm-admin $OUT $A 'pnpm run build:project:bundle'; count webpack-warm-admin $ASSETS/admin
done
echo DONE >> $OUT
