# TypeScript model checking POC

This opt-in experiment models a small part of WooCommerce in TypeScript and uses [tla-ts](https://github.com/aidenybai/tla-ts) to generate and check TLA+. It lives outside the release package, has its own dependency workspace, and does not run in the normal build or CI. The runner can be extended with more examples if the approach proves useful.

## Run it

Use the repository's Node version. From the repository root:

```sh
pnpm -C tools/model-checking-ts install --frozen-lockfile --ignore-scripts
pnpm -C tools/model-checking-ts check
```

The default command checks all four stock-reservation cases. The first three must finish without an invariant violation. The deliberately broken `split` case must violate `NoOverReservation`; a different failure makes the command fail.

Run a single case with:

```sh
pnpm -C tools/model-checking-ts check stock-reservation split
```

Generated specifications, configurations, counterexamples, and result summaries go under the ignored `artifacts/` directory. Edit `examples/stock-reservation/model.ts`, not its generated TLA+.

## Cross-check with TLC

The default check runs in Node and needs no Java. To also run the generated specifications with official TLC, install Java 11 or later and download `tla2tools.jar` from the [TLA+ tools releases](https://github.com/tlaplus/tlaplus/releases).

```sh
TLA2TOOLS_JAR=/absolute/path/to/tla2tools.jar \
    pnpm -C tools/model-checking-ts check:tlc
```

Set `TLA_JAVA` if the Java executable is not on `PATH`. Both checkers must agree on the expected outcome and on the reachable-state count for passing cases. Matching counts are a consistency check, not an equivalence proof.

## What the example checks

The model follows [ReserveStock](../../plugins/woocommerce/src/Checkout/Helpers/ReserveStock.php) and the product data store's stock query. It includes replacement of an existing hold, expiration, release, the three-attempt database retry limit, and cleanup after failure. The `found-rows` case changes how an unchanged upsert reports its affected-row count.

Two orders request stock for one product. The invariant says their active holds must not exceed stock. The `split` case separates checking availability from writing the hold, allowing both orders to admit the last unit.

Each exported state-changing function is one atomic model action. The normal reservation action assumes InnoDB serializes the stock check and write through the product's stock metadata lock. The [SQL locking change](https://github.com/woocommerce/woocommerce/pull/65325) describes that contract. A passing model does not establish that the database provides it.

The scope is fixed inventory, unpaid orders, positive integer quantities, one reservation call in flight per order, backorders disabled, and default hooks. Payments, inventory updates, multiple products, concurrent calls for the same order, and the opt-in exclusion of a shopper's stale holds are outside the model. This checks the model; it does not execute the PHP implementation or establish conformance to it.

## Add another example

Create `examples/<name>/model.ts` and `cases.json`, then run `pnpm check <name>` from this directory. Models import their finite constants and parameter types from `./parameters`; the runner generates that file for each case.

The manifest contains default `constants`, finite `types`, and named `cases`. A case can override either default and can name an expected `violation`. A case without `violation` must pass. Keep state spaces finite and small enough to explore completely.

The compiler is pinned for this POC and isolated from WooCommerce's TypeScript toolchain. No broader framework or CI adoption is proposed yet.
