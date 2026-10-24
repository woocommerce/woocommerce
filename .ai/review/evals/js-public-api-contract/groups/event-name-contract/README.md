# Supported event-name contracts

The original patch rejects non-string event names. This pair tests whether the reviewer treats incidental coercion as a supported input contract.

The change deliberately stops accepting numeric names. Valid string names keep the same behavior. The public Tracks package documents and types `eventName` as a string.

The control inverts the guard, which rejects every valid string name. That change prevents supported events from reaching the queue.

Count warnings about former numeric coercion separately from failures involving supported strings. A real consumer or a documented contract can establish an actionable compatibility problem.

Read [the original change](https://github.com/woocommerce/woocommerce/commit/e8409074363258daff3d66b02feaa75fa6894f72) and [the public event-name contract](https://github.com/woocommerce/woocommerce/blob/25ca8e656937378844d3899f8f1c6d0a9307921d/packages/js/tracks/src/index.ts) for the source behavior.

## Prepare the pair

Run these commands from the WooCommerce repository that contains this group. The source repository must already contain both commits listed in `sources.json`.

```sh
python3 .ai/review/evals/js-public-api-contract/groups/event-name-contract/prepare.py \
  --policy-repo "$PWD" \
  --source-repo /path/to/woocommerce-with-historical-objects \
  --output /path/to/new-event-name-contract-checkout
```

The script checks the original patch and both patch hashes. It uses local Git objects and creates a separate checkout. Keep both input repositories available while using that checkout.

The generated checkout registers this pair alongside the 160 default cases. Preparation never calls a model or changes an input repository.

```sh
a8c-review eval --path /path/to/new-event-name-contract-checkout \
  --guideline js-public-api-contract --group event-name-contract --plan
a8c-review eval --path /path/to/new-event-name-contract-checkout \
  --guideline js-public-api-contract --group event-name-contract
```

The second command uses the configured review engines and consumes their allowance. Follow the main [eval setup instructions](../../../README.md) before running it.

## Interpret the results

The original uses the complete historical patch and source tree. The control starts from the same parent and introduces the described defect.

The `quiet` expectation includes suppressed findings. The control expectation checks the finding's primary file and line. Read both surfaced and suppressed findings before recording target frequencies.

These cases test review behavior. They do not run a full WordPress installation or measure production-wide accuracy.
