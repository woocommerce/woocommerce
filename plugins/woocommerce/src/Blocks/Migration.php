<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * Takes care of the migrations.
 *
 * @since 2.5.0
 */
class Migration {
	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * Please note that these functions are invoked when WooCommerce Blocks is updated from a previous version,
	 * but NOT when WooCommerce Blocks is newly installed.
	 *
	 * @var array
	 */
	private $db_upgrades = array(
		'10.3.0' => array(
			'wc_blocks_update_1030_blockified_product_grid_block',
		),
		'11.2.0' => array(
			'wc_blocks_update_1120_rename_checkout_template',
			'wc_blocks_update_1120_rename_cart_template',
		),
	);

	/**
	 * Runs all the necessary migrations.
	 *
	 * @var array
	 */
	public function run_migrations() {
		$current_db_version = get_option( Options::WC_BLOCK_VERSION, '' );
		$schema_version     = get_option( 'wc_blocks_db_schema_version', '' );

		// This check is necessary because the version was not being set in the database until 10.3.0.
		// Checking wc_blocks_db_schema_version determines if it's a fresh install (value will be empty)
		// or an update from WC Blocks older than 10.3.0 (it will have some value). In the latter scenario
		// we should run the migration.
		// We can remove this check in the next months.
		if ( ! empty( $schema_version ) && ( empty( $current_db_version ) ) ) {
			$this->wc_blocks_update_1030_blockified_product_grid_block();
		}

		if ( empty( $current_db_version ) ) {
			// This is a fresh install, so we don't need to run any migrations.
			return;
		}

		foreach ( $this->db_upgrades as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					$this->{$update_callback}();
				}
			}
		}
	}

	/**
	 * Set a flag to indicate if the blockified Product Grid Block should be rendered by default.
	 */
	public static function wc_blocks_update_1030_blockified_product_grid_block() {
		update_option( Options::WC_BLOCK_USE_BLOCKIFIED_PRODUCT_GRID_BLOCK_AS_TEMPLATE, wc_bool_to_string( false ) );
	}

	/**
	 * Rename `checkout` template to `page-checkout`.
	 */
	public static function wc_blocks_update_1120_rename_checkout_template() {
		$template = get_block_template( BlockTemplateUtils::PLUGIN_SLUG . '//checkout', 'wp_template' );

		if ( $template && ! empty( $template->wp_id ) ) {
			if ( ! defined( 'WP_POST_REVISIONS' ) ) {
				// This prevents a fatal error when ran outside of admin context.
				define( 'WP_POST_REVISIONS', false );
			}
			wp_update_post(
				array(
					'ID'        => $template->wp_id,
					'post_name' => 'page-checkout',
				)
			);
		}
	}

	/**
	 * Rename `cart` template to `page-cart`.
	 */
	public static function wc_blocks_update_1120_rename_cart_template() {
		$template = get_block_template( BlockTemplateUtils::PLUGIN_SLUG . '//cart', 'wp_template' );

		if ( $template && ! empty( $template->wp_id ) ) {
			if ( ! defined( 'WP_POST_REVISIONS' ) ) {
				// This prevents a fatal error when ran outside of admin context.
				define( 'WP_POST_REVISIONS', false );
			}
			wp_update_post(
				array(
					'ID'        => $template->wp_id,
					'post_name' => 'page-cart',
				)
			);
		}
	}
}
