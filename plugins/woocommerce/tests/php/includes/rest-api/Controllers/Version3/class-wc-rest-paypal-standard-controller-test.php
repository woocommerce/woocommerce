<?php

/**
 * class WC_REST_Paypal_Standard_Controller_Test.
 * PayPal Standard Controller tests for V3 REST API.
 */
class WC_REST_Paypal_Standard_Controller_Test  extends WC_REST_Unit_Test_Case {
	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->endpoint = new WC_REST_Paypal_Standard_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}
}
