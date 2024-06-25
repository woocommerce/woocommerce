<?php
/**
 * This is an automatically generated baseline for Phan issues.
 * When Phan is invoked with --load-baseline=path/to/baseline.php,
 * The pre-existing issues listed in this file won't be emitted.
 *
 * This file can be updated by invoking Phan with --save-baseline=path/to/baseline.php
 * (can be combined with --load-baseline)
 */
return [
    // # Issue statistics:
    // PhanPluginRedundantAssignment : 4 occurrences
    // PhanTypeSuspiciousNonTraversableForeach : 1 occurrence
    // PhanUndeclaredMethod : 1 occurrence
    // PhanUndeclaredMethodInCallable : 1 occurrence

    // Currently, file_suppressions and directory_suppressions are the only supported suppressions
    'file_suppressions' => [
        'src/class-checkout-flow.php' => ['PhanPluginRedundantAssignment'],
        'src/class-universal.php' => ['PhanPluginRedundantAssignment', 'PhanUndeclaredMethodInCallable'],
        'src/class-woo-analytics-trait.php' => ['PhanTypeSuspiciousNonTraversableForeach', 'PhanUndeclaredMethod'],
    ],
    // 'directory_suppressions' => ['src/directory_name' => ['PhanIssueName1', 'PhanIssueName2']] can be manually added if needed.
    // (directory_suppressions will currently be ignored by subsequent calls to --save-baseline, but may be preserved in future Phan releases)
];
