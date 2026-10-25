<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\Internal\EmailEditor\TransactionalEmailPersonalizer;
use WC_Unit_Test_Case;

/**
 * Tests for the TransactionalEmailPersonalizer class.
 */
class TransactionalEmailPersonalizerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var TransactionalEmailPersonalizer
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new TransactionalEmailPersonalizer();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_email_editor_integration_personalizer_context_data' );
		Email_Editor_Container::container()->get( Personalization_Tags_Registry::class )->unregister( '[test/text-tag]' );
		parent::tearDown();
	}

	/**
	 * @testdox Should pass the rendering context through so text-typed tag values stay raw in text context and are escaped in the HTML default.
	 */
	public function test_rendering_context_is_passed_through(): void {
		Email_Editor_Container::container()->get( Personalization_Tags_Registry::class )->register(
			new Personalization_Tag(
				'Text Tag',
				'test/text-tag',
				'Test',
				function () {
					return 'Tom & Jerry';
				},
				array(),
				null,
				array(),
				Personalization_Tag::VALUE_TYPE_TEXT
			)
		);

		$mock_email = $this->createMock( \WC_Email::class );
		$mock_email->method( 'get_recipient' )->willReturn( 'test@example.com' );
		$mock_email->object = new \stdClass();

		$content = 'Hi <!--[test/text-tag]-->';

		$this->assertSame(
			'Hi Tom & Jerry',
			$this->sut->personalize_transactional_content( $content, $mock_email, Personalizer::RENDERING_CONTEXT_TEXT ),
			'Text context should keep the raw value'
		);
		$this->assertSame(
			'Hi Tom &amp; Jerry',
			$this->sut->personalize_transactional_content( $content, $mock_email ),
			'HTML default should escape text-typed values'
		);
	}

	/**
	 * @testdox Should preserve wp_user set by the filter when core cannot derive it from the email object.
	 */
	public function test_wp_user_set_by_filter_is_preserved(): void {
		$mock_user  = $this->createMock( \WP_User::class );
		$mock_email = $this->createMock( \WC_Email::class );
		$mock_email->method( 'get_recipient' )->willReturn( 'test@example.com' );
		$mock_email->object = new \stdClass();

		add_filter(
			'woocommerce_email_editor_integration_personalizer_context_data',
			function ( $context ) use ( $mock_user ) {
				$context['wp_user'] = $mock_user;
				return $context;
			}
		);

		$result = $this->sut->prepare_context_data( array(), $mock_email );

		$this->assertSame( $mock_user, $result['wp_user'], 'wp_user set by the filter should be preserved' );
	}

	/**
	 * @testdox Should set wp_user from WP_User email object when no filter overrides it.
	 */
	public function test_wp_user_set_from_wp_user_object(): void {
		$mock_user  = $this->createMock( \WP_User::class );
		$mock_email = $this->createMock( \WC_Email::class );
		$mock_email->method( 'get_recipient' )->willReturn( 'test@example.com' );
		$mock_email->object = $mock_user;

		$result = $this->sut->prepare_context_data( array(), $mock_email );

		$this->assertSame( $mock_user, $result['wp_user'], 'wp_user should be set from WP_User email object' );
	}

	/**
	 * @testdox Should set wp_user to null when email object is not WP_User or WC_Order and no filter overrides.
	 */
	public function test_wp_user_null_for_unknown_object_type(): void {
		$mock_email = $this->createMock( \WC_Email::class );
		$mock_email->method( 'get_recipient' )->willReturn( 'test@example.com' );
		$mock_email->object = new \stdClass();

		$result = $this->sut->prepare_context_data( array(), $mock_email );

		$this->assertNull( $result['wp_user'], 'wp_user should be null when object type is unknown and no filter overrides' );
	}

	/**
	 * @testdox Should provide core defaults to the filter callback.
	 */
	public function test_filter_receives_core_defaults(): void {
		$received_context = null;
		$mock_email       = $this->createMock( \WC_Email::class );
		$mock_email->method( 'get_recipient' )->willReturn( 'test@example.com' );
		$mock_email->object = new \stdClass();

		add_filter(
			'woocommerce_email_editor_integration_personalizer_context_data',
			function ( $context ) use ( &$received_context ) {
				$received_context = $context;
				return $context;
			}
		);

		$this->sut->prepare_context_data( array(), $mock_email );

		$this->assertArrayHasKey( 'recipient_email', $received_context, 'Filter should receive recipient_email' );
		$this->assertSame( 'test@example.com', $received_context['recipient_email'] );
		$this->assertArrayHasKey( 'wp_user', $received_context, 'Filter should receive wp_user' );
		$this->assertArrayHasKey( 'wc_email', $received_context, 'Filter should receive wc_email' );
		$this->assertArrayHasKey( 'order', $received_context, 'Filter should receive order' );
	}

	/**
	 * @testdox Should fall back to core context when filter returns non-array.
	 */
	public function test_fallback_to_core_context_when_filter_returns_non_array(): void {
		$mock_email = $this->createMock( \WC_Email::class );
		$mock_email->method( 'get_recipient' )->willReturn( 'test@example.com' );
		$mock_email->object = new \stdClass();

		add_filter(
			'woocommerce_email_editor_integration_personalizer_context_data',
			function () {
				return 'not_an_array';
			}
		);

		$result = $this->sut->prepare_context_data( array(), $mock_email );

		$this->assertArrayHasKey( 'recipient_email', $result, 'Fallback should contain core defaults' );
		$this->assertSame( 'test@example.com', $result['recipient_email'] );
		$this->assertArrayHasKey( 'wp_user', $result, 'Fallback should contain wp_user from core' );
		$this->assertArrayHasKey( 'wc_email', $result, 'Fallback should contain wc_email from core' );
		$this->assertArrayHasKey( 'order', $result, 'Fallback should contain order from core' );
	}
}
