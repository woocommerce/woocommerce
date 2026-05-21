// QAO-185 bridge aggregator.
// Re-exports the blocks e2e utils barrel via the @woocommerce/e2e-utils tsconfig alias
// (added to tests/e2e-pw/tsconfig.json in PR #1, Step 3).
// Rewritten in PR #7 once utils moves (PR #4/#5) have landed.
// eslint-disable-next-line import/no-unresolved
export * from '@woocommerce/e2e-utils';
