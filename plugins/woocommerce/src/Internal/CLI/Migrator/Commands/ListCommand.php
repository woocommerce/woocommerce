<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\CLI\Migrator\Commands;

use WP_CLI;

/**
 * Lists all registered migration platforms.
 */
class ListCommand extends BaseCommand {

	/**
	 * Lists all registered migration platforms.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wc migrator list
	 *
	 * @param array $args       The positional arguments.
	 * @param array $assoc_args The associative arguments.
	 *
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$platforms = $this->platform_registry->get_platforms();

		if ( empty( $platforms ) ) {
			WP_CLI::line( 'No migration platforms are registered.' );
			return;
		}

		$formatted_items = array();
		foreach ( $platforms as $id => $details ) {
			$formatted_items[] = array(
				'id'      => $id,
				'name'    => $details['name'] ?? '',
				'fetcher' => $details['fetcher'] ?? '',
				'mapper'  => $details['mapper'] ?? '',
			);
		}

		WP_CLI\Utils\format_items(
			'table',
			$formatted_items,
			array( 'id', 'name', 'fetcher', 'mapper' )
		);
	}
}
