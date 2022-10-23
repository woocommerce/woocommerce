<?php
/**
 * Beta Tester Plugin Live Branches feature class
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester Live Branches Feature Class.
 */
class WC_Beta_Tester_Live_Branches {

	

	/**
	 * Constructor
	 */
	public function __construct() {
		
		// $this->includes();
		$this->register_live_branches_page();
	}

  protected function register_live_branches_page() {
    if ( ! function_exists( 'wc_admin_register_page' ) ) {
      return;
    }

		wc_admin_register_page(
			array(
				'id'       => 'wc-beta-tester-live-branches',
				'title'    => __( 'Live Branches', 'woocommerce-beta-tester' ),
				'path'     => 'live-branches',
				'parent'   => 'woocommerce',
				'capability' => 'read',
				'nav_args' => array(
					'order'  => 10,
					'parent' => 'woocommerce',
				),
			)
		);
  }
}

return new WC_Beta_Tester_Live_Branches();
