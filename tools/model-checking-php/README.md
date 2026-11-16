# PHP state exploration POC

This opt-in experiment expresses the stock-reservation model and its safety checks in PHP. A small reusable explorer visits every reachable state in the finite model and returns a shortest counterexample when an invariant fails. It runs outside WordPress and the release package, with no dependencies or normal CI integration.

This is finite-state safety checking inspired by the TLA+ example. It is not a TLA+ implementation: it does not parse TLA+, check temporal properties, establish fairness or liveness, or prove conformance of the production code.

## Run it

Use PHP 7.4 or later. From the repository root:

```sh
php -d memory_limit=512M tools/model-checking-php/run.php
php tools/model-checking-php/tests/ExplorerChecks.php
```

The default command checks all four stock-reservation cases. `small`, `larger`, and `found-rows` must exhaust their graphs without a violation. `split` must produce `NoOverReservation`; a different violation or incomplete exploration fails the command.

Check one case with:

```sh
php -d memory_limit=512M tools/model-checking-php/run.php split
```

Each line of output is JSON. A counterexample includes its actions and state snapshots, starting with `init`. Composer scripts provide the same checks if Composer is available, but installation is not required.

## What the model checks

The example follows [ReserveStock](../../plugins/woocommerce/src/Checkout/Helpers/ReserveStock.php): replacing an existing hold, expiration, release, three-attempt database retries, and cleanup after failure. `found-rows` changes the result of an unchanged upsert.

The invariant requires the two orders' active holds to fit within one product's stock. The deliberately broken `split` variant checks availability in one transition and writes the reservation in another, so both orders can reserve the last unit.

The normal admission transition assumes InnoDB serializes the stock check and write through the product's stock metadata lock. The [SQL locking change](https://github.com/woocommerce/woocommerce/pull/65325) describes that contract. Passing this model does not verify database locking.

The scope is fixed inventory, unpaid orders, positive integer quantities, one reservation call in flight per order, disabled backorders, and default hooks. Payments, inventory updates, multiple products, concurrent calls for the same order, and the opt-in exclusion of a shopper's stale holds are outside the model. No WordPress or database code runs here.

## Extend it

Implement `Model` with an initial JSON-compatible array, every enabled transition, and a method naming the first violated invariant. Pass it to `Explorer::check()`. The stock example in `examples/StockReservation.php` is one implementation.

The explorer uses exact encoded state keys and breadth-first traversal. Array ordering is part of state identity, so models should keep a consistent state shape. The state limit throws an error instead of returning a partial pass. A terminal state is allowed; deadlock detection is outside this POC.

The standalone checks cover initial-state violations, cycle handling, shortest counterexamples, and the exploration limit. The PHPStan configuration can be run with the existing WooCommerce development dependency:

```sh
composer --working-dir=plugins/woocommerce exec -- phpstan analyse \
    --configuration=../../tools/model-checking-php/phpstan.neon --memory-limit=2G
```

This is an experiment in authoring and inspecting safety models in familiar code. It proposes no production change or broader framework adoption.
