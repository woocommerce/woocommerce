<?php
/**
 * Finance admin menu.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Finance top-level admin menu and its wc-admin pages.
 *
 * @internal
 */
class FinanceMenu implements RegisterHooksInterface {

	/**
	 * The id of the top-level Finance page.
	 *
	 * @var string
	 */
	public const PARENT_ID = 'woocommerce-finance';

	/**
	 * The wc-admin path of the Overview page, which is also the top-level menu target.
	 *
	 * @var string
	 */
	public const OVERVIEW_PATH = '/finance/overview';

	/**
	 * The wc-admin path of the Payouts page.
	 *
	 * @var string
	 */
	public const PAYOUTS_PATH = '/finance/payouts';

	/**
	 * The admin menu position, between Payments (56) and Analytics (57).
	 *
	 * @var float
	 */
	public const MENU_POSITION = 56.5;

	/**
	 * The capability required to see the Finance pages. Matches the finance REST API permission.
	 *
	 * @var string
	 */
	public const CAPABILITY = 'manage_woocommerce';

	/**
	 * The user preference that stores the last provider selected on the Payouts page.
	 *
	 * @var string
	 */
	public const LAST_PROVIDER_USER_DATA_FIELD = 'payments_finance_last_provider';

	/**
	 * Register hooks.
	 *
	 * @since 11.2.0
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'handle_admin_menu' ) );
		add_filter( 'woocommerce_admin_get_user_data_fields', array( $this, 'handle_woocommerce_admin_get_user_data_fields' ) );
	}

	/**
	 * Handle the admin_menu hook: add the Finance menu and its pages.
	 *
	 * The top-level item bypasses PageController::register_page() because that method rounds the
	 * position to an integer, which would place Finance after Analytics.
	 *
	 * @internal
	 */
	public function handle_admin_menu(): void {
		$parent = $this->get_parent_page();

		add_menu_page(
			$parent['title'],
			$parent['title'],
			$parent['capability'],
			$parent['path'],
			array( PageController::class, 'page_wrapper' ),
			$parent['icon'],
			$parent['position']
		);

		PageController::get_instance()->connect_page( $parent );

		foreach ( $this->get_sub_pages() as $page ) {
			wc_admin_register_page( $page );
		}
	}

	/**
	 * Handle the woocommerce_admin_get_user_data_fields filter: expose the Finance user preferences.
	 *
	 * @internal
	 *
	 * @param mixed $fields The user data fields exposed over the WP user endpoint.
	 * @return array
	 */
	public function handle_woocommerce_admin_get_user_data_fields( $fields ): array {
		if ( ! is_array( $fields ) ) {
			$fields = array();
		}

		$fields[] = self::LAST_PROVIDER_USER_DATA_FIELD;

		return $fields;
	}

	/**
	 * Get the definition of the top-level Finance page, in the PageController::connect_page() format.
	 *
	 * @return array
	 */
	public function get_parent_page(): array {
		return array(
			'id'         => self::PARENT_ID,
			'title'      => __( 'Finance', 'woocommerce' ),
			'path'       => PageController::PAGE_ROOT . '&path=' . self::OVERVIEW_PATH,
			'capability' => self::CAPABILITY,
			'icon'       => 'dashicons-money-alt',
			'position'   => self::MENU_POSITION,
			'js_page'    => true,
		);
	}

	/**
	 * Get the definitions of the Finance sub pages, in the wc_admin_register_page() format.
	 *
	 * The first sub page repeats the parent path so that the top-level menu gets a named first entry.
	 *
	 * @return array[]
	 */
	public function get_sub_pages(): array {
		return array(
			array(
				'id'         => self::PARENT_ID . '-overview',
				'title'      => __( 'Overview', 'woocommerce' ),
				'parent'     => self::PARENT_ID,
				'path'       => self::OVERVIEW_PATH,
				'capability' => self::CAPABILITY,
			),
			array(
				'id'         => self::PARENT_ID . '-payouts',
				'title'      => __( 'Payouts', 'woocommerce' ),
				'parent'     => self::PARENT_ID,
				'path'       => self::PAYOUTS_PATH,
				'capability' => self::CAPABILITY,
			),
		);
	}
}
