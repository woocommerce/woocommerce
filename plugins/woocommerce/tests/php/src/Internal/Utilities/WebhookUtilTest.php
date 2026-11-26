<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\WebhookUtil;
use WC_Unit_Test_Case;
use WC_Webhook;

/**
 * Tests for the WebhookUtil class.
 */
class WebhookUtilTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WebhookUtil
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WebhookUtil::class );
	}

	/**
	 * @testdox The delete user screen explains what "Attribute all content to" does to webhooks only when WordPress offers that option.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $user_has_content Whether the user owns content. WordPress offers to reassign content only then.
	 */
	public function test_delete_user_warning_explains_reassignment_only_when_offered( bool $user_has_content ): void {
		$user_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );

		$webhook = new WC_Webhook();
		$webhook->set_user_id( $user_id );
		$webhook->set_topic( 'order.created' );
		$webhook->set_delivery_url( 'https://example.com/webhook' );
		$webhook->save();

		if ( $user_has_content ) {
			self::factory()->post->create( array( 'post_author' => $user_id ) );
		}

		ob_start();
		$this->sut->maybe_render_user_with_webhooks_warning( wp_get_current_user(), array( $user_id ) );
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'has created 1 WooCommerce webhook.', $output, 'The warning should count the webhooks of the user.' );
		$this->assertSame(
			$user_has_content,
			str_contains( $output, 'Attribute all content to' ),
			'The reassignment text should show only when WordPress offers to reassign content.'
		);
	}
}
