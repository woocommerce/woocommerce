<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Cli\ExportCli;
use Automattic\WooCommerce\Blueprint\Cli\ImportCli;

$autoload_path = __DIR__ . '/../vendor/autoload.php';
if ( file_exists( $autoload_path ) ) {
	require_once $autoload_path;
}
/**
 * Class Cli.
 *
 * This class is included and execute from WC_CLI(class-wc-cli.php) to register
 * WP CLI commands.
 */
class Cli {
	/**
	 * Register WP CLI commands.
	 *
	 * @return void
	 */
	public static function register_commands() {
		\WP_CLI::add_command(
			'wc blueprint import',
			function ( $args, $assoc_args ) {
				$import = new ImportCli( $args[0] );
				$import->run( $assoc_args );
			},
			array(
				'synopsis' => array(
					array(
						'type'     => 'positional',
						'name'     => 'schema-path',
						'optional' => false,
					),
					array(
						'type'     => 'assoc',
						'name'     => 'show-messages',
						'optional' => true,
						'options'  => array( 'all', 'error', 'info', 'debug' ),
					),
				),
				'when'     => 'after_wp_load',
			)
		);

		\WP_CLI::add_command(
			'wc blueprint export',
			function ( $args, $assoc_args ) {
				$export = new ExportCli( $args[0] );
				$steps  = array();
				$format = $assoc_args['format'] ?? 'json';

				if ( isset( $assoc_args['steps'] ) ) {
					$steps = array_map(
						function ( $step ) {
							return trim( $step );
						},
						explode( ',', $assoc_args['steps'] )
					);
				}
				$export->run(
					array(
						'steps'  => $steps,
						'format' => $format,
					)
				);
			},
			array(
				'synopsis' => array(
					array(
						'type'     => 'positional',
						'name'     => 'save-to',
						'optional' => false,
					),
					array(
						'type'     => 'assoc',
						'name'     => 'steps',
						'optional' => true,
					),
					array(
						'type'     => 'assoc',
						'name'     => 'format',
						'optional' => true,
						'default'  => 'json',
						'options'  => array( 'json', 'zip' ),
					),
				),
				'when'     => 'after_wp_load',
			)
		);
	}
}
