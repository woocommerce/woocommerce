<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Notes;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Internal\Admin\Notes\WooSubscriptionsNotes;
use WC_Unit_Test_Case;

/**
 * Tests for the WooSubscriptionsNotes class.
 */
class WooSubscriptionsNotesTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooSubscriptionsNotes
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WooSubscriptionsNotes();
	}

	/**
	 * @testdox Should link the expiring note's Enable Autorenew action to My Subscriptions with inbox UTM params.
	 */
	public function test_expiring_note_action_has_inbox_utm_params(): void {
		$this->sut->add_or_update_subscription_expiring(
			array(
				'product_id'   => 101,
				'product_name' => 'Test Extension',
				'expires'      => time() + 30 * DAY_IN_SECONDS,
			)
		);

		$action = $this->get_note_action( 101, 'enable-autorenew' );

		$this->assertSame(
			'https://woocommerce.com/my-account/my-subscriptions/?utm_source=inbox_notification&utm_campaign=pu_inbox_enable_autorenew',
			$action->query,
			'Enable Autorenew should link to My Subscriptions with the inbox UTM params'
		);
	}

	/**
	 * @testdox Should link the expired note's Renew Subscription action to the product page with inbox UTM params.
	 */
	public function test_expired_note_action_has_inbox_utm_params(): void {
		$this->sut->add_or_update_subscription_expired( $this->get_expired_subscription( 102 ) );

		$action = $this->get_note_action( 102, 'renew-subscription' );

		$this->assertSame(
			'https://woocommerce.com/products/test-extension/?utm_source=inbox_notification&utm_campaign=pu_inbox_renew',
			$action->query,
			'Renew Subscription should link to the product page with the inbox UTM params'
		);
	}

	/**
	 * @testdox Should update the action URL of an expired note saved before the UTM params were added.
	 */
	public function test_existing_expired_note_gets_new_action_url(): void {
		$this->sut->add_or_update_subscription_expired( $this->get_expired_subscription( 103 ) );
		$note = $this->sut->find_note_for_product_id( 103 );
		$note->clear_actions();
		$note->add_action( 'renew-subscription', 'Renew Subscription', 'https://woocommerce.com/products/test-extension/' );
		$note->save();

		$this->sut->add_or_update_subscription_expired( $this->get_expired_subscription( 103 ) );

		$action = $this->get_note_action( 103, 'renew-subscription' );
		$this->assertSame(
			'https://woocommerce.com/products/test-extension/?utm_source=inbox_notification&utm_campaign=pu_inbox_renew',
			$action->query,
			'A stale expired note should pick up the new action URL'
		);
	}

	/**
	 * @testdox Should leave the Renew Subscription URL empty when the subscription has no product page.
	 */
	public function test_expired_note_without_product_page_has_empty_action_url(): void {
		$subscription                = $this->get_expired_subscription( 104 );
		$subscription['product_url'] = '';

		$this->sut->add_or_update_subscription_expired( $subscription );

		$this->assertSame( '', $this->get_note_action( 104, 'renew-subscription' )->query, 'UTM params should not be added to an empty URL' );
	}

	/**
	 * Builds an expired subscription record as returned by the WooCommerce.com API.
	 *
	 * @param int $product_id The product ID.
	 * @return array
	 */
	private function get_expired_subscription( int $product_id ): array {
		return array(
			'product_id'   => $product_id,
			'product_name' => 'Test Extension',
			'product_url'  => 'https://woocommerce.com/products/test-extension/',
			'expires'      => time() - 10 * DAY_IN_SECONDS,
		);
	}

	/**
	 * Loads the saved subscription note for a product and returns one of its actions.
	 *
	 * @param int    $product_id  The product ID.
	 * @param string $action_name The action name.
	 * @return object
	 */
	private function get_note_action( int $product_id, string $action_name ): object {
		$note = $this->sut->find_note_for_product_id( $product_id );
		$this->assertInstanceOf( Note::class, $note, 'A subscription note should exist for the product' );

		$actions = wp_list_filter( $note->get_actions(), array( 'name' => $action_name ) );
		$this->assertCount( 1, $actions, "The note should have one {$action_name} action" );

		return reset( $actions );
	}
}
