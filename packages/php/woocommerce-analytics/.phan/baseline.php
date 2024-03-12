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
    // PhanUndeclaredFunction : 65+ occurrences
    // PhanUndeclaredClassMethod : 20+ occurrences
    // PhanUndeclaredClassInstanceof : 10+ occurrences
    // PhanUndeclaredConstant : 5 occurrences
    // PhanPluginRedundantAssignment : 4 occurrences
    // PhanUndeclaredTypeParameter : 2 occurrences
    // PhanTypeSuspiciousNonTraversableForeach : 1 occurrence
    // PhanUndeclaredMethod : 1 occurrence
    // PhanUnextractableAnnotationSuffix : 1 occurrence

    // Currently, file_suppressions and directory_suppressions are the only supported suppressions
    'file_suppressions' => [
        'src/class-checkout-flow.php' => ['PhanPluginRedundantAssignment', 'PhanUndeclaredClassInstanceof', 'PhanUndeclaredClassMethod', 'PhanUndeclaredFunction'],
        'src/class-my-account.php' => ['PhanUndeclaredFunction'],
        'src/class-universal.php' => ['PhanPluginRedundantAssignment', 'PhanUndeclaredClassInstanceof', 'PhanUndeclaredClassMethod', 'PhanUndeclaredFunction', 'PhanUnextractableAnnotationSuffix'],
        'src/class-woo-analytics-trait.php' => ['PhanTypeSuspiciousNonTraversableForeach', 'PhanUndeclaredClassInstanceof', 'PhanUndeclaredClassMethod', 'PhanUndeclaredConstant', 'PhanUndeclaredFunction', 'PhanUndeclaredMethod', 'PhanUndeclaredTypeParameter'],
        'src/class-woocommerce-analytics.php' => ['PhanUndeclaredClassMethod', 'PhanUndeclaredConstant', 'PhanUndeclaredFunction'],
    ],
    // 'directory_suppressions' => ['src/directory_name' => ['PhanIssueName1', 'PhanIssueName2']] can be manually added if needed.
    // (directory_suppressions will currently be ignored by subsequent calls to --save-baseline, but may be preserved in future Phan releases)
];
