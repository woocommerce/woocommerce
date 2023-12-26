<?php

/**
 * Tests relating to the WC_Webhook_Data_Store class.
 */
class WC_Webhook_Data_Store_Test extends WC_Unit_Test_Case {

	/**
	 * Verify that the search_webhooks() method returns the correct results when searching by user ID.
	 */
	public function test_search_webhooks_by_user_id() {

		$store = new WC_Webhook_Data_Store();

		$this->assertEmpty(
			$store->search_webhooks(
				array(
					'user_id' => 1,
				)
			)
		);

		$webhook1 = new WC_Webhook();
		$webhook1->set_user_id( 1 );
		$webhook1->save();

		$webhook2 = new WC_Webhook();
		$webhook2->set_user_id( 2 );
		$webhook2->save();

		$this->assertSame(
			array( $webhook1->get_id() ),
			$store->search_webhooks(
				array(
					'user_id' => 1,
				)
			)
		);
		$this->assertSame(
			array( $webhook2->get_id() ),
			$store->search_webhooks(
				array(
					'user_id' => 2,
				)
			)
		);
	}

}
