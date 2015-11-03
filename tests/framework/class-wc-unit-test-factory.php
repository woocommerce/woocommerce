<?php
/**
 * WC Unit Test Factory
 *
 * Provides WooCommerce-specific factories.
 *
 * @since 2.2
 */
class WC_Unit_Test_Factory extends WP_UnitTest_Factory {

	/** @var \WC_Unit_Test_Factory_For_Webhook */
	public $webhook;

	/** @var \WC_Unit_Test_Factory_For_Webhook_Delivery */
	public $webhook_delivery;

	/**
	 * Setup factories.
	 */
	public function __construct() {

		parent::__construct();

		$this->webhook = new WC_Unit_Test_Factory_For_Webhook( $this );
		$this->webhook_delivery = new WC_Unit_Test_Factory_For_Webhook_Delivery( $this );
	}

}
