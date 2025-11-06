<?php
/**
 * Base configuration for Phan. Project configs should require this and
 * make any necessary changes.
 *
 * @package woocommerce/woocommerce
 */

// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- This is not WordPress
// phpcs:disable WordPress.WP.CapitalPDangit.MisspelledInComment -- It's filename constants.
// phpcs:disable WordPress.WP.CapitalPDangit.MisspelledInText -- It's filename constants.

/**
 * Create a Phan configuration.
 *
 * @param string $dir Project directory.
 * @param array  $options Additional options.
 *   - directory_list: (array) Directories to scan, rather than scanning the whole project.
 *   - exclude_analysis_directory_list: (array) Directories to exclude from analysis.
 *   - exclude_file_list: (array) Individual files to exclude.
 *   - exclude_file_regex: (array) Additional regexes to exclude. Will be anchored at the start.
 *   - file_list: (array) Additional individual files to scan.
 *   - globals_type_map: (array) Map of global name (no `$`) to Phan type. Class names should be prefixed with `\`.
 *   - parse_file_list: (array) Files to parse but not analyze. Equivalent to listing in both 'file_list' and 'exclude_analysis_directory_list'.
 *   - php_extensions_needed: (array) Stubs provided by Phan to use with various PHP extensions like xdebug, zip, etc.
 *   - stubs: (array) Predefined stubs to load. Default is `array( 'wordpress', 'wp-cli' )`.
 *      - wordpress: Stubs from php-stubs/wordpress-stubs, php-stubs/wordpress-tests-stubs, php-stubs/wp-cli-stubs, .phan/stubs/wordpress-constants.php, and .phan/stubs/wordpress-globals.jsonc.
 *      - wp-cli: Stubs from php-stubs/wp-cli-stubs.
 *   - +stubs: (array) Like 'stubs', but setting this does not clear the defaults.
 *   - suppress_issue_types: (array) Issues to suppress for the entire project.
 *   - unsuppress_issue_types: (array) Default-suppressed issues to unsuppress for the project.
 * @return array Phan config.
 * @throws InvalidArgumentException If something is detected as invalid.
 */
function make_phan_config( $dir, $options = array() ) {
	$options += array(
		'directory_list'                  => array( '.' ),
		'exclude_analysis_directory_list' => array(),
		'exclude_file_list'               => array(),
		'exclude_file_regex'              => array(),
		'file_list'                       => array(),
		'globals_type_map'                => array(),
		'parse_file_list'                 => array(),
		'php_extensions_needed'           => array(),
		'stubs'                           => array( 'wordpress', 'wp-cli' ),
		'+stubs'                          => array(),
		'suppress_issue_types'            => array(),
		'unsuppress_issue_types'          => array(),
	);

	$root = dirname( __DIR__ );

	$stubs          = array();
	$global_stubs   = array();
	$internal_stubs = array();

	foreach ( array_merge( $options['stubs'], $options['+stubs'] ) as $stub ) {
		switch ( $stub ) {
			case 'wordpress':
				$stubs[]        = "$root/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php";
				$stubs[]        = "$root/vendor/php-stubs/wordpress-tests-stubs/wordpress-tests-stubs.php";
				$stubs[]        = "$root/.phan/stubs/wordpress-constants.php";
				$global_stubs[] = "$root/.phan/stubs/wordpress-globals.jsonc";
				break;
			case 'wp-cli':
				$stubs[] = "$root/vendor/php-stubs/wp-cli-stubs/wp-cli-stubs.php";
				$stubs[] = "$root/vendor/php-stubs/wp-cli-stubs/wp-cli-commands-stubs.php";
				$stubs[] = "$root/vendor/php-stubs/wp-cli-stubs/wp-cli-i18n-stubs.php";
				break;
			default:
				throw new InvalidArgumentException( "Unknown stub '$stub'" );
		}
	}

	$globals = array();
	foreach ( $global_stubs as $file ) {
		$contents = file_get_contents( $file );
		// Remove single-line comments.
		$contents = preg_replace( '#^\s*//.*$#m', '', $contents );
		$globals  = array_merge( $globals, json_decode( $contents, true ) );
	}

	'@phan-var non-empty-array $options["php_extensions_needed"]';
	foreach ( $options['php_extensions_needed'] as $stub ) {
		$stub_file_path = "$root/vendor/phan/phan/.phan/internal_stubs/$stub.phan_php";
		if ( ! file_exists( $stub_file_path ) ) {
			throw new InvalidArgumentException( "Cannot load internal stubs for '$stub': file $stub_file_path does not exist." );
		}
		$internal_stubs[ $stub ] = $stub_file_path;
	}

	$config = array(
		// Apparently this is only useful when upgrading from php 5, not for 7-to-8.
		'backward_compatibility_checks'          => false,

		// If we start depending on class_alias, we might need this true. For now we don't.
		'enable_class_alias_support'             => false,

		// Seems worthwhile to have these flagged for attention.
		// Probably either the type inference is wrong or the code could be simplified.
		'redundant_condition_detection'          => true,

		// Plugins to enable.
		'plugins'                                => array(
			'AddNeverReturnTypePlugin',
			'DuplicateArrayKeyPlugin',
			'DuplicateExpressionPlugin',
			'LoopVariableReusePlugin',
			'PHPUnitNotDeadCodePlugin',
			'PregRegexCheckerPlugin',
			'RedundantAssignmentPlugin',
			'SimplifyExpressionPlugin',
			'UnreachableCodePlugin',
			'UnusedSuppressionPlugin',
			'UseReturnValuePlugin',
		),

		// Override to hardcode existence and types of (non-builtin) globals in the global scope.
		'globals_type_map'                       => array_merge(
			$globals,
			$options['globals_type_map']
		),

		// Issues to disable globally.
		'suppress_issue_types'                   => array_merge(
			array_diff(
				array(
					// WordPress coding standards do not allow the `?:` operator.
					'PhanPluginDuplicateConditionalTernaryDuplication',
				),
				$options['unsuppress_issue_types']
			),
			$options['suppress_issue_types']
		),

		// Directories and individual files to parse (and, by default, analyze).
		// Values are relative to the project base, and must begin with `./` for the exclude_file_regex to work right.
		'directory_list'                         => $options['directory_list'],
		'file_list'                              => array_merge(
			array(
				// Otherwise it complains about the config files trying to call this function.
				__FILE__,
				// Assume everything uses PHPUnit.
				"$root/.phan/stubs/phpunit-stubs.php",
			),
			$stubs,
			$options['file_list'],
			$options['parse_file_list']
		),

		// Patterns to exclude files from parsing.
		'exclude_file_regex'                     => '@^(?:\./)?(?:' . implode(
			'|',
			array_merge(
				array(
					// Ignore any `test`, `tests`, `Test`, and `Tests` inside `vendor`.
					'vendor/.*/[tT]ests?/',
					// Ignore any `vendor` and `node_modules` inside `vendor`.
					'vendor/.*/(?:vendor|node_modules)/',
					// Yoast/phpunit-polyfills triggers a lot of PhanRedefinedXXX errors.
					'vendor/yoast/phpunit-polyfills/src/Polyfills/(?!.*_Empty).*\.php',
					'vendor/yoast/phpunit-polyfills/src/TestCases/TestCasePHPUnitLte7\.php',
					// Other stuff to ignore.
					'node_modules/',
					'tests/e2e/node_modules/',
					'\.cache/',
				),
				// PHPUnit has some stuff that doesn't work with Phan. We provide corrected stubs.
				// This file holds the vendor paths we stubbed.
				explode( "\n", trim( (string) file_get_contents( "$root/.phan/stubs/phpunit-dirs.txt" ) ) ),
				$options['exclude_file_regex']
			)
		) . ')@',

		// Specific files to exclude from parsing.
		'exclude_file_list'                      => $options['exclude_file_list'],

		// List directories that will be excluded from analysis (but will still be parsed).
		// Note anything here needs to be listed in `directory_list` or `file_list` to be parsed in the first place.
		'exclude_analysis_directory_list'        => array_merge(
			array(
				'vendor/',
				'.phan/stubs/',
				"$root/vendor/",
				"$root/.phan/",
			),
			$options['exclude_analysis_directory_list'],
			$options['parse_file_list']
		),

		// @see https://github.com/phan/phan/wiki/How-To-Use-Stubs#internal-stubs
		'autoload_internal_extension_signatures' => $internal_stubs,
	);

	// Read minimum version from composer.json, if any is set.
	$composer = json_decode( file_get_contents( "$dir/composer.json" ), true );
	if ( isset( $composer['require']['php'] ) && preg_match( '/^>=\s*(\d+\.\d+)(?:\.\d+)?\s*$/', $composer['require']['php'], $m ) ) {
		$config['minimum_target_php_version'] = $m[1];
		$config['target_php_version']         = $m[1];
	}

	return $config;
}

