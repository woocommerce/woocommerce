<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\PlatformRegistry;
use WP_CLI;

/**
 * Lists all registered migration platforms.
 */
class ListCommand {

	/**
	 * The platform registry.
	 *
	 * @var PlatformRegistry
	 */
	private PlatformRegistry $platform_registry;

	/**
	 * Initialize the command with its dependencies.
	 *
	 * @param PlatformRegistry $platform_registry The platform registry.
	 *
	 * @internal
	 */
	final public function init( PlatformRegistry $platform_registry ): void {
		$this->platform_registry = $platform_registry;
	}

	/**
	 * Lists all registered migration platforms.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wc migrate list
	 *
	 * @param array $args       The positional arguments (unused).
	 * @param array $assoc_args The associative arguments (unused).
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		unset( $args, $assoc_args );

		$platforms = $this->platform_registry->get_platforms();

		if ( empty( $platforms ) ) {
			WP_CLI::line( 'No migration platforms are registered.' );
			return;
		}

		$formatted_items = array();
		$platform_count  = count( $platforms );
		$current_index   = 0;

		foreach ( $platforms as $id => $details ) {
			$formatted_items[] = array(
				'id'      => $id,
				'name'    => $details['name'] ?? '',
				'fetcher' => $details['fetcher'] ?? '',
				'mapper'  => $details['mapper'] ?? '',
			);

			// Add separator row between platforms (but not after the last one).
			++$current_index;
			if ( $current_index < $platform_count ) {
				$formatted_items[] = array(
					'id'      => str_repeat( '-', 20 ),
					'name'    => str_repeat( '-', 25 ),
					'fetcher' => str_repeat( '-', 30 ),
					'mapper'  => str_repeat( '-', 30 ),
				);
			}
		}

		WP_CLI\Utils\format_items(
			'table',
			$formatted_items,
			array( 'id', 'name', 'fetcher', 'mapper' )
		);
	}
}
