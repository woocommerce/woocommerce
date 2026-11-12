# Hook callback re-entrancy cases

These reduced cases follow the GUID and attachment-metadata cycle introduced in [#70198](https://github.com/woocommerce/woocommerce/pull/70198), fixed in [#70782](https://github.com/woocommerce/woocommerce/pull/70782), and covered by the guideline from [#70788](https://github.com/woocommerce/woocommerce/pull/70788).

- G41A-v2 adds an unguarded metadata read to a GUID callback. The behavior harness supplies an external media-plugin callback that fires the GUID hook again; it is not included in the reviewer fixture.
- G41B-v2 removes `finally` cleanup from a guard. A caught metadata-provider exception leaves later GUID reads bypassing the download check.
- G41C-v2 adds a guard with `finally` cleanup. Nested calls preserve the supplied GUID, and the outer call still hides downloadable attachments.
- G41D-v1 refactors an argument-only callback without adding filtered reads.

Run the bounded behavior check without WordPress or model calls:

```sh
php .ai/review/evals/hook-callback-reentrancy/verify.php
```

The check models the relevant hook dispatch and metadata short-circuit behavior. It stops recursive calls at a small depth and checks exception recovery, repeated reads, nested and outer return values, and ordinary attachments. It is not a WordPress or Real Media Library integration test.

Run the suite through a compatible review CLI:

```sh
a8c-review eval --guideline hook-callback-reentrancy --plan
a8c-review eval --guideline hook-callback-reentrancy
```

The second command consumes the configured model allowance. A passing behavior check or plan does not establish model recall. These cases do not evaluate the guideline's direct-database-query exemption.
