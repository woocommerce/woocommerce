# JavaScript unit tests

Workspace unit tests use `@wordpress/scripts` 36 and Vitest 5. Install dependencies with `pnpm install`, then run every JavaScript unit-test suite from the repository root:

```bash
pnpm test:js
```

The root suite also covers workflow scripts and the mocked Blocks database-restorer utility.

Run a package, watch its tests, or update its snapshots:

```bash
pnpm --filter=@woocommerce/components test:js
pnpm --filter=@woocommerce/components test:js --watch
pnpm --filter=@woocommerce/components test:js --update
pnpm --filter=@woocommerce/components test:js --coverage
```

Each package owns a `vitest.config.mjs` that extends `createTestConfig` from this package. The shared configuration resolves workspace source, transforms JSX in existing JavaScript files, and provides jsdom, React cleanup, and checks for unexpected console output. Tooling tests use Node without DOM setup. Test discovery includes `test/`, `__tests__/`, and `.test`/`.spec` files; end-to-end tests have their own runners.

Import test APIs explicitly from `vitest`. Use `vi.mock` for module mocks, `vi.importActual` for partial mocks, and `await import()` after `vi.resetModules()` when testing module initialization. Mock factories are hoisted; use `vi.hoisted` for values they need and `vi.doMock` for mocks installed during a test.

PHP test commands are unchanged. See the [WordPress consumer migration guide](https://github.com/WordPress/gutenberg/blob/HEAD/packages/scripts/docs/vitest-migration.md) for runner differences and supported versions.
