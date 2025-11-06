<?php
/**
 * This configuration will be read and overlaid on top of the
 * default configuration. Command-line arguments will be applied
 * after this file is read.
 *
 * @package woocommerce/woocommerce
 */

// This is not WordPress.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase, WordPress.WP.AlternativeFunctions

// Require base config.
require __DIR__ . '/config.base.php';

$config = make_phan_config(
	dirname( __DIR__ ),
	array(
		'directory_list'                  => array(
			'.',
			'vendor/automattic',
			'vendor/composer',
			'vendor/maxmind-db',
			'vendor/opis',
			'vendor/woocommerce',
			'vendor/wordpress',
		),
		'exclude_analysis_directory_list' => array(
			'vendor/',
			'.phan/',
			'tests/',
			'packages/',
			'lib/',
		),
		'exclude_file_regex'              => array(
			// Don't analyze test files.
			'tests/',
			// Don't analyze packages (those should be analyzed separately if needed).
			'packages/',
			// Don't analyze vendored libraries.
			'lib/',
		),
		'suppress_issue_types'            => array(
			// Suppress some common issues that might be noisy initially.
			'PhanUnreferencedUseNormal',
			'PhanUnreferencedClosure',
		),
	)
);

return $config;

