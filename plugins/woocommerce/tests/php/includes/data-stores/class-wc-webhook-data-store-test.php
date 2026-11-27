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

	/**
	 * @testdox Saving an unrelated edit preserves the newer delivery URL and author.
	 */
	public function test_unrelated_save_keeps_newer_delivery_url_and_author(): void {
		$first_author  = self::factory()->user->create();
		$second_author = self::factory()->user->create();
		$webhook       = new WC_Webhook();
		$webhook->set_status( 'paused' );
		$webhook->set_delivery_url( 'https://example.com/original' );
		$webhook->set_user_id( $first_author );
		$webhook->save();

		$earlier_copy = new WC_Webhook( $webhook->get_id() );
		$webhook->set_delivery_url( 'https://example.com/current' );
		$webhook->set_user_id( $second_author );
		$webhook->save();

		$earlier_copy->set_name( 'Integration webhook' );
		$earlier_copy->save();

		$saved = new WC_Webhook( $webhook->get_id() );
		$this->assertSame( 'Integration webhook', $saved->get_name() );
		$this->assertSame( 'https://example.com/current', $saved->get_delivery_url() );
		$this->assertSame( $second_author, $saved->get_user_id() );
	}

}
