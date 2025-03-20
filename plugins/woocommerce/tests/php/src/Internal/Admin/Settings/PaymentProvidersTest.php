<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentProviders;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentExtensionSuggestions;
use Automattic\WooCommerce\Internal\Admin\Suggestions\PaymentExtensionSuggestions as ExtensionSuggestions;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;
use WC_Gateway_BACS;
use WC_Gateway_Cheque;
use WC_Gateway_COD;
use WC_Gateway_Paypal;

/**
 * Payment Providers service test.
 *
 * @class PaymentProviders
 */
class PaymentProvidersTest extends WC_Unit_Test_Case {

	/**
	 * @var PaymentProviders
	 */
	protected $sut;

	/**
	 * @var PaymentExtensionSuggestions|MockObject
	 */
	protected $mock_extension_suggestions;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->mock_extension_suggestions = $this->getMockBuilder( PaymentExtensionSuggestions::class )
			->disableOriginalConstructor()
			->getMock();

		$this->sut = new PaymentProviders();
		$this->sut->init( $this->mock_extension_suggestions );
	}

	/**
	 * Test getting payment gateways.
	 */
	public function test_get_payment_gateways() {
		// Arrange.
		$this->load_core_paypal_pg();

		// Act.
		$data = $this->sut->get_payment_gateways();

		// We have the core PayPal gateway registered and the 3 offline payment methods.
		$this->assertCount( 4, $data );
		$this->assertEquals(
			array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID, WC_Gateway_COD::ID, WC_Gateway_Paypal::ID ),
			// Extract the IDs from the list of objects.
			array_values(
				array_map(
					function ( $gateway ) {
						return $gateway->id;
					},
					$data
				)
			)
		);

		// Clean up.
		$this->sut->reset_memo();
	}

	/**
	 * Test getting payment gateway base details.
	 */
	public function test_get_payment_gateway_base_details() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'woocommerce_payments',
			array(
				'enabled'                     => true,
				'account_connected'           => true,
				'needs_setup'                 => true,
				'test_mode'                   => true,
				'dev_mode'                    => true,
				'onboarding_started'          => true,
				'onboarding_completed'        => true,
				'test_mode_onboarding'        => true,
				'plugin_slug'                 => 'woocommerce-payments',
				'plugin_file'                 => 'woocommerce-payments/woocommerce-payments.php',
				'recommended_payment_methods' => array(
					// Basic PM.
					array(
						'id'      => 'basic',
						// No order, should be last.
						'enabled' => true,
						'title'   => 'Title',
					),
					// Basic PM with priority instead of order.
					array(
						'id'       => 'basic2',
						'priority' => 30,
						'enabled'  => false,
						'title'    => 'Title',
					),
					array(
						'id'          => 'card',
						'order'       => 20,
						'enabled'     => true,
						'required'    => true,
						'title'       => '<b>Credit/debit card (required)</b>', // All tags should be stripped.
						// Paragraphs and line breaks should be stripped.
						'description' => '<p><strong>Accepts</strong> <b>all major</b></br><em>credit</em> and <a href="#" target="_blank">debit cards</a>.</p>',
						'icon'        => 'https://example.com/card-icon.png',
					),
					array(
						'id'          => 'woopay',
						'order'       => 10,
						'enabled'     => false,
						'title'       => 'WooPay',
						'description' => 'WooPay express checkout',
						// Not a good URL.
						'icon'        => 'not_good_url/icon.svg',
					),
					// Invalid PM, should be ignored. No data.
					array(),
					// Invalid PM, should be ignored. No ID.
					array( 'title' => 'Card' ),
					// Invalid PM, should be ignored. No title.
					array( 'id' => 'card' ),
				),
			),
		);

		// Act.
		$gateway_details = $this->sut->get_payment_gateway_base_details( $fake_gateway, 999 );

		// Assert that have all the details.
		$this->assertArrayHasKey( 'id', $gateway_details, 'Gateway `id` entry is missing' );
		$this->assertArrayHasKey( '_order', $gateway_details, 'Gateway `_order` entry is missing' );
		$this->assertArrayHasKey( 'title', $gateway_details, 'Gateway `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $gateway_details, 'Gateway `description` entry is missing' );
		$this->assertArrayHasKey( 'supports', $gateway_details, 'Gateway `supports` entry is missing' );
		$this->assertIsList( $gateway_details['supports'], 'Gateway `supports` entry is not a list' );
		$this->assertArrayHasKey( 'state', $gateway_details, 'Gateway `state` entry is missing' );
		$this->assertArrayHasKey( 'enabled', $gateway_details['state'], 'Gateway `state[enabled]` entry is missing' );
		$this->assertTrue( $gateway_details['state']['enabled'], 'Gateway `state[enabled]` entry is not true' );
		$this->assertArrayHasKey( 'account_connected', $gateway_details['state'], 'Gateway `state[account_connected]` entry is missing' );
		$this->assertTrue( $gateway_details['state']['account_connected'], 'Gateway `state[account_connected]` entry is not true' );
		$this->assertArrayHasKey( 'needs_setup', $gateway_details['state'], 'Gateway `state[needs_setup]` entry is missing' );
		$this->assertTrue( $gateway_details['state']['needs_setup'], 'Gateway `state[needs_setup]` entry is not true' );
		$this->assertArrayHasKey( 'test_mode', $gateway_details['state'], 'Gateway `state[test_mode]` entry is missing' );
		$this->assertTrue( $gateway_details['state']['test_mode'], 'Gateway `state[test_mode]` entry is not true' );
		$this->assertArrayHasKey( 'dev_mode', $gateway_details['state'], 'Gateway `state[dev_mode]` entry is missing' );
		$this->assertTrue( $gateway_details['state']['dev_mode'], 'Gateway `state[dev_mode]` entry is not true' );
		$this->assertArrayHasKey( 'management', $gateway_details, 'Gateway `management` entry is missing' );
		$this->assertArrayHasKey( '_links', $gateway_details['management'], 'Gateway `management[_links]` entry is missing' );
		$this->assertArrayHasKey( 'settings', $gateway_details['management']['_links'], 'Gateway `management[_links][settings]` entry is missing' );
		$this->assertArrayHasKey( 'plugin', $gateway_details, 'Gateway `plugin` entry is missing' );
		$this->assertArrayHasKey( 'slug', $gateway_details['plugin'], 'Gateway `plugin[slug]` entry is missing' );
		$this->assertSame( 'woocommerce-payments', $gateway_details['plugin']['slug'] );
		$this->assertArrayHasKey( 'file', $gateway_details['plugin'], 'Gateway `plugin[file]` entry is missing' );
		$this->assertSame( 'woocommerce-payments/woocommerce-payments', $gateway_details['plugin']['file'] ); // No more .php extension.
		$this->assertArrayHasKey( 'status', $gateway_details['plugin'], 'Gateway `plugin[status]` entry is missing' );
		$this->assertSame( PaymentProviders::EXTENSION_ACTIVE, $gateway_details['plugin']['status'] );
		$this->assertArrayHasKey( 'onboarding', $gateway_details, 'Gateway `onboarding` entry is missing' );
		$this->assertArrayHasKey( 'state', $gateway_details['onboarding'], 'Gateway `onboarding[state]` entry is missing' );
		$this->assertArrayHasKey( 'started', $gateway_details['onboarding']['state'], 'Gateway `onboarding[state][started]` entry is missing' );
		$this->assertTrue( $gateway_details['onboarding']['state']['started'], 'Gateway `onboarding[state][started]` entry is not false' );
		$this->assertArrayHasKey( 'completed', $gateway_details['onboarding']['state'], 'Gateway `onboarding[state][completed]` entry is missing' );
		$this->assertTrue( $gateway_details['onboarding']['state']['completed'], 'Gateway `onboarding[state][completed]` entry is not false' );
		$this->assertArrayHasKey( 'test_mode', $gateway_details['onboarding']['state'], 'Gateway `onboarding[state][test_mode]` entry is missing' );
		$this->assertTrue( $gateway_details['onboarding']['state']['test_mode'], 'Gateway `onboarding[state][test_mode]` entry is not false' );
		$this->assertArrayHasKey( '_links', $gateway_details['onboarding'], 'Gateway `onboarding[_links]` entry is missing' );
		$this->assertArrayHasKey( 'onboard', $gateway_details['onboarding']['_links'], 'Gateway `onboarding[_links][onboard]` entry is missing' );
		$this->assertArrayHasKey( 'recommended_payment_methods', $gateway_details['onboarding'], 'Gateway `onboarding[recommended_payment_methods]` entry is missing' );
		$this->assertCount( 4, $gateway_details['onboarding']['recommended_payment_methods'] ); // Receives recommended PMs.
		$this->assertSame(
			array(
				array(
					'id'          => 'woopay',
					'_order'      => 0,
					'enabled'     => false,
					'required'    => false,
					'title'       => 'WooPay',
					'description' => 'WooPay express checkout',
					'icon'        => '', // The icon with an invalid URL is ignored.
				),
				array(
					'id'          => 'card',
					'_order'      => 1,
					'enabled'     => true,
					'required'    => true,
					'title'       => 'Credit/debit card (required)',
					'description' => '<strong>Accepts</strong> <b>all major</b><em>credit</em> and <a href="#" target="_blank">debit cards</a>.',
					'icon'        => 'https://example.com/card-icon.png',
				),
				array(
					'id'          => 'basic2',
					'_order'      => 2,
					'enabled'     => false,
					'required'    => false,
					'title'       => 'Title',
					'description' => '',
					'icon'        => '',
				),
				array(
					'id'          => 'basic',
					'_order'      => 3,
					'enabled'     => true,
					'required'    => false,
					'title'       => 'Title',
					'description' => '',
					'icon'        => '',
				),
			),
			$gateway_details['onboarding']['recommended_payment_methods']
		);
	}

	/**
	 * Test getting payment gateway base details when a custom provider is present that is mapped using a wildcard.
	 */
	public function test_get_payment_gateway_base_details_with_custom_provider_wildcard_mapping() {
		// Arrange.
		$fake_gateway = new FakePaymentGateway(
			'mollie_wc_gateway_bogus', // This should be matched by the wildcard mapping 'mollie_wc_gateway_*'.
			array(
				'enabled'                     => true,
				'plugin_slug'                 => 'mollie',
				'plugin_file'                 => 'mollie/mollie.php',
				'recommended_payment_methods' => array(),
			),
		);

		update_option( 'mollie-payments-for-woocommerce_test_mode_enabled', 'yes' );
		update_option( 'mollie-payments-for-woocommerce_test_api_key', 'bogus_key' );

		// Act.
		$gateway_details = $this->sut->get_payment_gateway_base_details( $fake_gateway, 999 );

		// Assert that the custom provider supplied details are returned.
		$this->assertSame( 'mollie_wc_gateway_bogus', $gateway_details['id'] );
		// This settings URL is provided by the custom provider.
		$this->assertSame( admin_url( 'admin.php?page=wc-settings&tab=mollie_settings&section=mollie_payment_methods' ), $gateway_details['management']['_links']['settings']['href'] );
		$this->assertTrue( $gateway_details['state']['test_mode'] ); // It should be in test mode because of the DB options. The custom provider logic handles this.

		// Clean up.
		delete_option( 'mollie-payments-for-woocommerce_test_mode_enabled' );
		delete_option( 'mollie-payments-for-woocommerce_test_api_key' );
	}

	/**
	 * Test getting the plugin slug of a payment gateway instance.
	 */
	public function test_get_payment_gateway_plugin_slug() {
		// Arrange.
		$this->load_core_paypal_pg();

		// Act.
		$paypal_gateway = array_filter(
			WC()->payment_gateways()->payment_gateways,
			function ( $gateway ) {
				return WC_Gateway_Paypal::ID === $gateway->id;
			}
		);
		$paypal_gateway = reset( $paypal_gateway );
		$slug           = $this->sut->get_payment_gateway_plugin_slug( $paypal_gateway );

		// Assert.
		// The PayPal gateway is a core gateway, so the slug is 'woocommerce'.
		$this->assertSame( 'woocommerce', $slug );
	}

	/**
	 * Test getting the payment extension suggestions.
	 */
	public function test_get_extension_suggestions_empty() {
		// Arrange.
		$location = 'US';

		$this->mock_extension_suggestions->expects( $this->once() )
										->method( 'get_country_extensions' )
										->with( $location )
										->willReturn( array() );

		// Act.
		$suggestions = $this->sut->get_extension_suggestions( $location );

		// Assert.
		$this->assertIsArray( $suggestions );
		$this->assertArrayHasKey( 'preferred', $suggestions );
		$this->assertArrayHasKey( 'other', $suggestions );
		$this->assertEmpty( $suggestions['preferred'] );
		$this->assertEmpty( $suggestions['other'] );
	}

	/**
	 * Test getting the payment extension suggestions with no PSP enabled.
	 */
	public function test_get_extension_suggestions_with_no_psp_enabled() {
		// Arrange.
		$location         = 'US';
		$base_suggestions = array(
			array(
				'id'                => 'suggestion1',
				'_priority'         => 1,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 1',
				'description'       => 'Description 1',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug1',
				),
				'image'             => 'http://example.com/image1.png',
				'icon'              => 'http://example.com/icon1.png',
				'short_description' => null,
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url1',
					),
				),
				'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion2',
				'_priority'         => 2,
				'_type'             => ExtensionSuggestions::TYPE_APM,
				'title'             => 'Suggestion 2',
				'description'       => 'Description 2',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug2',
				),
				'image'             => 'http://example.com/image2.png',
				'icon'              => 'http://example.com/icon2.png',
				'short_description' => 'short description 2',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url2',
					),
				),
				'tags'              => array( 'tag2', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion3',
				'_priority'         => 3,
				'_type'             => ExtensionSuggestions::TYPE_BNPL,
				'title'             => 'Suggestion 3',
				'description'       => 'Description 3',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug3',
				),
				'image'             => 'http://example.com/image3.png',
				'icon'              => 'http://example.com/icon3.png',
				'short_description' => 'short description 3',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url3',
					),
				),
				'tags'              => array( 'tag3' ),
			),
			array(
				'id'                => 'suggestion4',
				'_priority'         => 4,
				'_type'             => ExtensionSuggestions::TYPE_EXPRESS_CHECKOUT,
				'title'             => 'Suggestion 4',
				'description'       => 'Description 4',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug4',
				),
				'image'             => 'http://example.com/image4.png',
				'icon'              => 'http://example.com/icon4.png',
				'short_description' => 'short description 4',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url4',
					),
				),
				'tags'              => array( 'tag4' ),
			),
			array(
				'id'                => 'suggestion5',
				'_priority'         => 5,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 5',
				'description'       => 'Description 5',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug5',
				),
				'image'             => 'http://example.com/image5.png',
				'icon'              => 'http://example.com/icon5.png',
				'short_description' => 'short description 5',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url5',
					),
				),
				'tags'              => array( 'tag5' ),
			),
		);

		$this->mock_extension_suggestions->expects( $this->once() )
										->method( 'get_country_extensions' )
										->with( $location )
										->willReturn( $base_suggestions );

		// Act.
		$suggestions = $this->sut->get_extension_suggestions( $location );

		// Assert.
		$this->assertIsArray( $suggestions );
		$this->assertArrayHasKey( 'preferred', $suggestions );
		$this->assertCount( 2, $suggestions['preferred'] );
		$this->assertArrayHasKey( 'other', $suggestions );
		// There are no BNPLs or Express Checkout suggestions because there is no PSP enabled. Only PSPs are returned.
		$this->assertCount( 1, $suggestions['other'] );
		// The first suggestion is the preferred PSP.
		$this->assertSame( 'suggestion1', $suggestions['preferred'][0]['id'] );
		// The second suggestion is the APM.
		$this->assertSame( 'suggestion2', $suggestions['preferred'][1]['id'] );
		// The fifth suggestion is in the other list.
		$this->assertSame( 'suggestion5', $suggestions['other'][0]['id'] );

		// Ensure we have all the details for the preferred suggestions.
		$pref_suggestion = $suggestions['preferred'][0];
		$this->assertArrayHasKey( 'id', $pref_suggestion, 'Suggestion `id` entry is missing' );
		$this->assertSame( 'suggestion1', $pref_suggestion['id'] );
		$this->assertArrayHasKey( '_priority', $pref_suggestion, 'Suggestion `_priority` entry is missing' );
		$this->assertIsInteger( $pref_suggestion['_priority'], 'Suggestion `_priority` entry is not an integer' );
		$this->assertSame( 1, $pref_suggestion['_priority'] );
		$this->assertArrayHasKey( '_type', $pref_suggestion, 'Suggestion `_type` entry is missing' );
		$this->assertSame( ExtensionSuggestions::TYPE_PSP, $pref_suggestion['_type'] );
		$this->assertArrayHasKey( 'title', $pref_suggestion, 'Suggestion `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $pref_suggestion, 'Suggestion `description` entry is missing' );
		$this->assertArrayHasKey( 'plugin', $pref_suggestion, 'Suggestion `plugin` entry is missing' );
		$this->assertIsArray( $pref_suggestion['plugin'] );
		$this->assertArrayHasKey( '_type', $pref_suggestion['plugin'], 'Suggestion `plugin[_type]` entry is missing' );
		$this->assertArrayHasKey( 'slug', $pref_suggestion['plugin'], 'Suggestion `plugin[slug]` entry is missing' );
		$this->assertArrayHasKey( 'status', $pref_suggestion['plugin'], 'Suggestion `plugin[status]` entry is missing' );
		// The plugin should be not installed.
		$this->assertSame( PaymentProviders::EXTENSION_NOT_INSTALLED, $pref_suggestion['plugin']['status'] );
		$this->assertArrayHasKey( 'icon', $pref_suggestion, 'Suggestion `icon` entry is missing' );
		$this->assertArrayHasKey( 'links', $pref_suggestion, 'Suggestion `links` entry is missing' );
		$this->assertIsArray( $pref_suggestion['links'] );
		$this->assertNotEmpty( $pref_suggestion['links'] );
		$this->assertArrayHasKey( '_type', $pref_suggestion['links'][0], 'Suggestion `link[_type]` entry is missing' );
		$this->assertArrayHasKey( 'url', $pref_suggestion['links'][0], 'Suggestion `link[url]` entry is missing' );
		$this->assertArrayHasKey( 'tags', $pref_suggestion, 'Suggestion `tags` entry is missing' );
		$this->assertIsList( $pref_suggestion['tags'] );
		// It should have the recommended tag.
		$this->assertContains( ExtensionSuggestions::TAG_PREFERRED, $pref_suggestion['tags'] );
		// The category should be PSP.
		$this->assertSame( PaymentProviders::CATEGORY_PSP, $pref_suggestion['category'] );

		// Ensure we have all the details for the other suggestions.
		$other_suggestion = $suggestions['other'][0];
		$this->assertArrayHasKey( 'id', $other_suggestion, 'Suggestion `id` entry is missing' );
		$this->assertSame( 'suggestion5', $other_suggestion['id'] );
		$this->assertArrayHasKey( '_priority', $other_suggestion, 'Suggestion `_priority` entry is missing' );
		$this->assertIsInteger( $other_suggestion['_priority'], 'Suggestion `_priority` entry is not an integer' );
		$this->assertSame( 5, $other_suggestion['_priority'] );
		$this->assertArrayHasKey( '_type', $other_suggestion, 'Suggestion `_type` entry is missing' );
		$this->assertSame( ExtensionSuggestions::TYPE_PSP, $other_suggestion['_type'] );
		$this->assertArrayHasKey( 'title', $other_suggestion, 'Suggestion `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $other_suggestion, 'Suggestion `description` entry is missing' );
		$this->assertArrayHasKey( 'plugin', $other_suggestion, 'Suggestion `plugin` entry is missing' );
		$this->assertIsArray( $other_suggestion['plugin'] );
		$this->assertArrayHasKey( '_type', $other_suggestion['plugin'], 'Suggestion `plugin[_type]` entry is missing' );
		$this->assertArrayHasKey( 'slug', $other_suggestion['plugin'], 'Suggestion `plugin[slug]` entry is missing' );
		$this->assertArrayHasKey( 'status', $other_suggestion['plugin'], 'Suggestion `plugin[status]` entry is missing' );
		// The plugin should be not installed.
		$this->assertSame( PaymentProviders::EXTENSION_NOT_INSTALLED, $other_suggestion['plugin']['status'] );
		$this->assertArrayHasKey( 'icon', $other_suggestion, 'Suggestion `icon` entry is missing' );
		$this->assertArrayHasKey( 'links', $other_suggestion, 'Suggestion `links` entry is missing' );
		$this->assertIsArray( $other_suggestion['links'] );
		$this->assertNotEmpty( $other_suggestion['links'] );
		$this->assertArrayHasKey( '_type', $other_suggestion['links'][0], 'Suggestion `link[_type]` entry is missing' );
		$this->assertArrayHasKey( 'url', $other_suggestion['links'][0], 'Suggestion `link[url]` entry is missing' );
		$this->assertArrayHasKey( 'tags', $other_suggestion, 'Suggestion `tags` entry is missing' );
		$this->assertIsList( $other_suggestion['tags'] );
		// The category should be PSP.
		$this->assertSame( PaymentProviders::CATEGORY_PSP, $other_suggestion['category'] );
	}

	/**
	 * Test getting the payment extension suggestions with no PSP enabled.
	 */
	public function test_get_extension_suggestions_with_psp_enabled() {
		// Arrange.
		$this->enable_core_paypal_pg();

		$location         = 'US';
		$base_suggestions = array(
			array(
				'id'                => 'suggestion1',
				'_priority'         => 1,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 1',
				'description'       => 'Description 1',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug1',
				),
				'image'             => 'http://example.com/image1.png',
				'icon'              => 'http://example.com/icon1.png',
				'short_description' => null,
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url1',
					),
				),
				'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion2',
				'_priority'         => 2,
				'_type'             => ExtensionSuggestions::TYPE_APM,
				'title'             => 'Suggestion 2',
				'description'       => 'Description 2',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug2',
				),
				'image'             => 'http://example.com/image2.png',
				'icon'              => 'http://example.com/icon2.png',
				'short_description' => 'short description 2',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url2',
					),
				),
				'tags'              => array( 'tag2', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion3',
				'_priority'         => 3,
				'_type'             => ExtensionSuggestions::TYPE_BNPL,
				'title'             => 'Suggestion 3',
				'description'       => 'Description 3',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug3',
				),
				'image'             => 'http://example.com/image3.png',
				'icon'              => 'http://example.com/icon3.png',
				'short_description' => 'short description 3',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url3',
					),
				),
				'tags'              => array( 'tag3' ),
			),
			array(
				'id'                => 'suggestion4',
				'_priority'         => 4,
				'_type'             => ExtensionSuggestions::TYPE_EXPRESS_CHECKOUT,
				'title'             => 'Suggestion 4',
				'description'       => 'Description 4',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug4',
				),
				'image'             => 'http://example.com/image4.png',
				'icon'              => 'http://example.com/icon4.png',
				'short_description' => 'short description 4',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url4',
					),
				),
				'tags'              => array( 'tag4' ),
			),
			array(
				'id'                => 'suggestion5',
				'_priority'         => 5,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 5',
				'description'       => 'Description 5',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug5',
				),
				'image'             => 'http://example.com/image5.png',
				'icon'              => 'http://example.com/icon5.png',
				'short_description' => 'short description 5',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url5',
					),
				),
				'tags'              => array( 'tag5' ),
			),
		);

		$this->mock_extension_suggestions->expects( $this->once() )
										->method( 'get_country_extensions' )
										->with( $location )
										->willReturn( $base_suggestions );

		// Act.
		$suggestions = $this->sut->get_extension_suggestions( $location );

		// Assert.
		$this->assertIsArray( $suggestions );
		$this->assertArrayHasKey( 'preferred', $suggestions );
		$this->assertCount( 2, $suggestions['preferred'] );
		$this->assertArrayHasKey( 'other', $suggestions );
		// The BNPLs and Express Checkout suggestions are included because there is a PSP enabled.
		$this->assertCount( 3, $suggestions['other'] );
		// The first suggestion is the preferred PSP.
		$this->assertSame( 'suggestion1', $suggestions['preferred'][0]['id'] );
		// The second suggestion is the preferred APM.
		$this->assertSame( 'suggestion2', $suggestions['preferred'][1]['id'] );
		// The rest are in the other list, ordered by priority.
		$this->assertSame( array( 'suggestion3', 'suggestion4', 'suggestion5' ), array_column( $suggestions['other'], 'id' ) );
	}

	/**
	 * Test getting the payment extension suggestions preferred options respect priority ASC.
	 */
	public function test_get_extension_suggestions_ordered_by_priority() {
		// Arrange.
		$location         = 'US';
		$base_suggestions = array(
			array(
				'id'                => 'suggestion1',
				'_priority'         => 100,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 1',
				'description'       => 'Description 1',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug1',
				),
				'image'             => 'http://example.com/image1.png',
				'icon'              => 'http://example.com/icon1.png',
				'short_description' => null,
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url1',
					),
				),
				'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion2',
				'_priority'         => 10,
				'_type'             => ExtensionSuggestions::TYPE_APM,
				'title'             => 'Suggestion 2',
				'description'       => 'Description 2',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug2',
				),
				'image'             => 'http://example.com/image2.png',
				'icon'              => 'http://example.com/icon2.png',
				'short_description' => 'short description 2',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url2',
					),
				),
				'tags'              => array( 'tag2', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion3',
				'_priority'         => 2,
				'_type'             => ExtensionSuggestions::TYPE_APM,
				'title'             => 'Suggestion 3',
				'description'       => 'Description 3',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug3',
				),
				'image'             => 'http://example.com/image3.png',
				'icon'              => 'http://example.com/icon3.png',
				'short_description' => 'short description 3',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url3',
					),
				),
				'tags'              => array( 'tag3', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion4',
				'_priority'         => 4,
				'_type'             => ExtensionSuggestions::TYPE_EXPRESS_CHECKOUT,
				'title'             => 'Suggestion 4',
				'description'       => 'Description 4',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug4',
				),
				'image'             => 'http://example.com/image4.png',
				'icon'              => 'http://example.com/icon4.png',
				'short_description' => 'short description 4',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url4',
					),
				),
				'tags'              => array( 'tag4' ),
			),
			array(
				'id'                => 'suggestion5',
				'_priority'         => 1,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 5',
				'description'       => 'Description 5',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug5',
				),
				'image'             => 'http://example.com/image5.png',
				'icon'              => 'http://example.com/icon5.png',
				'short_description' => 'short description 5',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url5',
					),
				),
				'tags'              => array( 'tag5', ExtensionSuggestions::TAG_PREFERRED ),
			),
		);

		$this->mock_extension_suggestions->expects( $this->once() )
										->method( 'get_country_extensions' )
										->with( $location )
										->willReturn( $base_suggestions );

		// Act.
		$suggestions = $this->sut->get_extension_suggestions( $location );

		// Assert.
		$this->assertIsArray( $suggestions );
		$this->assertArrayHasKey( 'preferred', $suggestions );
		$this->assertCount( 2, $suggestions['preferred'] );
		$this->assertArrayHasKey( 'other', $suggestions );
		// The fifth suggestion is the preferred PSP.
		$this->assertSame( 'suggestion5', $suggestions['preferred'][0]['id'] );
		// The third suggestion is the preferred APM.
		$this->assertSame( 'suggestion3', $suggestions['preferred'][1]['id'] );
	}

	/**
	 * Test getting the payment extension suggestions with hidden suggestions.
	 */
	public function test_get_extension_suggestions_with_hidden_suggestions() {
		// Arrange.
		// We have 5 suggestions, but two are hidden from the preferred places.
		update_user_meta(
			$this->store_admin_id,
			Payments::USER_PAYMENTS_NOX_PROFILE_KEY,
			array(
				'hidden_suggestions' => array(
					array(
						'id'        => 'suggestion1',
						'timestamp' => time(),
					),
					array(
						'id'        => 'suggestion2',
						'timestamp' => time(),
					),
				),
			)
		);

		$location         = 'US';
		$base_suggestions = array(
			array(
				'id'                => 'suggestion1', // This suggestion is hidden.
				'_priority'         => 1,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 1',
				'description'       => 'Description 1',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug1',
				),
				'image'             => 'http://example.com/image1.png',
				'icon'              => 'http://example.com/icon1.png',
				'short_description' => null,
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url1',
					),
				),
				'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion2', // This suggestion is hidden.
				'_priority'         => 2,
				'_type'             => ExtensionSuggestions::TYPE_APM,
				'title'             => 'Suggestion 2',
				'description'       => 'Description 2',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug2',
				),
				'image'             => 'http://example.com/image2.png',
				'icon'              => 'http://example.com/icon2.png',
				'short_description' => 'short description 2',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url2',
					),
				),
				'tags'              => array( 'tag2', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion3',
				'_priority'         => 3,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 3',
				'description'       => 'Description 3',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug3',
				),
				'image'             => 'http://example.com/image3.png',
				'icon'              => 'http://example.com/icon3.png',
				'short_description' => 'short description 3',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url3',
					),
				),
				'tags'              => array( 'tag3', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion4',
				'_priority'         => 4,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 4',
				'description'       => 'Description 4',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug4',
				),
				'image'             => 'http://example.com/image4.png',
				'icon'              => 'http://example.com/icon4.png',
				'short_description' => 'short description 4',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url4',
					),
				),
				'tags'              => array( 'tag4' ),
			),
			array(
				'id'                => 'suggestion5',
				'_priority'         => 10,
				'_type'             => ExtensionSuggestions::TYPE_APM,
				'title'             => 'Suggestion 5',
				'description'       => 'Description 5',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug5',
				),
				'image'             => 'http://example.com/image5.png',
				'icon'              => 'http://example.com/icon5.png',
				'short_description' => 'short description 5',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url5',
					),
				),
				'tags'              => array( 'tag5', ExtensionSuggestions::TAG_PREFERRED ),
			),
		);

		$this->mock_extension_suggestions->expects( $this->once() )
										->method( 'get_country_extensions' )
										->with( $location )
										->willReturn( $base_suggestions );

		// Act.
		$suggestions = $this->sut->get_extension_suggestions( $location );

		// Assert.
		$this->assertIsArray( $suggestions );
		$this->assertArrayHasKey( 'preferred', $suggestions );
		$this->assertCount( 2, $suggestions['preferred'] );
		$this->assertArrayHasKey( 'other', $suggestions );
		// The third suggestion is the preferred PSP.
		$this->assertSame( 'suggestion3', $suggestions['preferred'][0]['id'] );
		// The fifth suggestion is the preferred APM.
		$this->assertSame( 'suggestion5', $suggestions['preferred'][1]['id'] );

		// The rest are in the other list, ordered by priority.
		$this->assertCount( 3, $suggestions['other'] );
		$this->assertSame( array( 'suggestion1', 'suggestion2', 'suggestion4' ), array_column( $suggestions['other'], 'id' ) );
	}

	/**
	 * Test getting the payment extension suggestions when there are multiple suggestions with the same plugin slug.
	 */
	public function test_get_extension_suggestions_no_two_suggestions_with_the_same_plugin_slug() {
		// Arrange.
		$this->enable_core_paypal_pg();

		$location         = 'US';
		$base_suggestions = array(
			array(
				'id'                => 'suggestion1',
				'_priority'         => 1,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 1',
				'description'       => 'Description 1',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'duplicate-slug',
				),
				'image'             => 'http://example.com/image1.png',
				'icon'              => 'http://example.com/icon1.png',
				'short_description' => null,
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url1',
					),
				),
				'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion2',
				'_priority'         => 2,
				'_type'             => ExtensionSuggestions::TYPE_APM,
				'title'             => 'Suggestion 2',
				'description'       => 'Description 2',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'duplicate-slug1',
				),
				'image'             => 'http://example.com/image2.png',
				'icon'              => 'http://example.com/icon2.png',
				'short_description' => 'short description 2',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url2',
					),
				),
				'tags'              => array( 'tag2', ExtensionSuggestions::TAG_PREFERRED ),
			),
			array(
				'id'                => 'suggestion3',
				'_priority'         => 3,
				'_type'             => ExtensionSuggestions::TYPE_BNPL,
				'title'             => 'Suggestion 3',
				'description'       => 'Description 3',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'slug3',
				),
				'image'             => 'http://example.com/image3.png',
				'icon'              => 'http://example.com/icon3.png',
				'short_description' => 'short description 3',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url3',
					),
				),
				'tags'              => array( 'tag3' ),
			),
			array(
				'id'                => 'suggestion4',
				'_priority'         => 4,
				'_type'             => ExtensionSuggestions::TYPE_EXPRESS_CHECKOUT,
				'title'             => 'Suggestion 4',
				'description'       => 'Description 4',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'duplicate-slug1',
				),
				'image'             => 'http://example.com/image4.png',
				'icon'              => 'http://example.com/icon4.png',
				'short_description' => 'short description 4',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url4',
					),
				),
				'tags'              => array( 'tag4' ),
			),
			array(
				'id'                => 'suggestion5',
				'_priority'         => 5,
				'_type'             => ExtensionSuggestions::TYPE_PSP,
				'title'             => 'Suggestion 5',
				'description'       => 'Description 5',
				'plugin'            => array(
					'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
					'slug'  => 'duplicate-slug',
				),
				'image'             => 'http://example.com/image5.png',
				'icon'              => 'http://example.com/icon5.png',
				'short_description' => 'short description 5',
				'links'             => array(
					array(
						'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
						'url'   => 'url5',
					),
				),
				'tags'              => array( 'tag5' ),
			),
		);

		$this->mock_extension_suggestions->expects( $this->once() )
										->method( 'get_country_extensions' )
										->with( $location )
										->willReturn( $base_suggestions );

		// Act.
		$suggestions = $this->sut->get_extension_suggestions( $location );

		// Assert.
		$this->assertIsArray( $suggestions );
		$this->assertArrayHasKey( 'preferred', $suggestions );
		$this->assertCount( 2, $suggestions['preferred'] );
		// The first suggestion is the preferred PSP.
		$this->assertSame( 'suggestion1', $suggestions['preferred'][0]['id'] );
		// The second suggestion is the preferred APM.
		$this->assertSame( 'suggestion2', $suggestions['preferred'][1]['id'] );

		$this->assertArrayHasKey( 'other', $suggestions );
		// The BNPLs and Express Checkout suggestions are included because there is a PSP enabled.
		$this->assertCount( 1, $suggestions['other'] );
		$this->assertSame( 'suggestion3', $suggestions['other'][0]['id'] );
		// Suggestion4 is not present because a suggestion with the same plugin slug is already present (preferred APM).
		// Suggestion5 is not present because a suggestion with the same plugin slug is already present (preferred PSP).
	}

	/**
	 * Test getting the payment extension suggestions throws exception.
	 */
	public function test_get_extension_suggestions_throws() {
		// Arrange.
		$location = 'US';

		$this->mock_extension_suggestions->expects( $this->once() )
										->method( 'get_country_extensions' )
										->with( $location )
										->willThrowException( new \Exception() );

		// Assert.
		$this->expectException( \Exception::class );

		// Act.
		$this->sut->get_extension_suggestions( $location );
	}

	/**
	 * Test getting a single payment extension suggestion by ID.
	 */
	public function test_get_extension_suggestion_by_id() {
		// Arrange.
		$suggestion_id      = 'suggestion1';
		$suggestion_details = array(
			'id'                => $suggestion_id,
			'_priority'         => 1,
			'_type'             => ExtensionSuggestions::TYPE_PSP,
			'title'             => 'Suggestion 1',
			'description'       => 'Description 1',
			'plugin'            => array(
				'_type'  => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
				'slug'   => 'woocommerce', // Use WooCommerce because it is an installed plugin, obviously.
				'file'   => 'woocommerce/woocommerce',
				'status' => PaymentProviders::EXTENSION_INSTALLED,
			),
			'image'             => 'http://example.com/image1.png',
			'icon'              => 'http://example.com/icon1.png',
			'short_description' => null,
			'links'             => array(
				array(
					'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
					'url'   => 'url1',
				),
			),
			'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
		);

		$this->mock_extension_suggestions->expects( $this->once() )
										->method( 'get_by_id' )
										->with( $suggestion_id )
										->willReturn( $suggestion_details );

		// Act.
		$suggestion = $this->sut->get_extension_suggestion_by_id( $suggestion_id );

		// Assert.
		$this->assertSame( $suggestion_details, $suggestion );
	}

	/**
	 * Test getting a single payment extension suggestion by a plugin slug.
	 */
	public function test_get_extension_suggestion_by_plugin_slug() {
		// Arrange.
		$slug               = 'woocommerce'; // Use WooCommerce because it is an active plugin.
		$suggestion_details = array(
			'id'                => 'suggestion1',
			'_priority'         => 1,
			'_type'             => ExtensionSuggestions::TYPE_PSP,
			'title'             => 'Suggestion 1',
			'description'       => 'Description 1',
			'plugin'            => array(
				'_type'  => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
				'slug'   => $slug,
				'file'   => 'woocommerce/woocommerce',
				'status' => PaymentProviders::EXTENSION_INSTALLED,
			),
			'image'             => 'http://example.com/image1.png',
			'icon'              => 'http://example.com/icon1.png',
			'short_description' => null,
			'links'             => array(
				array(
					'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
					'url'   => 'url1',
				),
			),
			'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
		);

		$this->mock_extension_suggestions->expects( $this->once() )
										->method( 'get_by_plugin_slug' )
										->with( $slug )
										->willReturn( $suggestion_details );

		// Act.
		$suggestion = $this->sut->get_extension_suggestion_by_plugin_slug( $slug );

		// Assert.
		$this->assertSame( $suggestion_details, $suggestion );
	}

	/**
	 * Test getting the payment extension suggestions categories.
	 */
	public function test_get_extension_suggestions_categories() {
		// Act.
		$categories = $this->sut->get_extension_suggestion_categories();

		// Assert.
		$this->assertIsArray( $categories );
		$this->assertCount( 4, $categories );
	}

	/**
	 * Test hiding a payment extension suggestion.
	 */
	public function test_hide_extension_suggestion() {
		// Arrange.
		$suggestion_id      = 'suggestion1';
		$suggestion_details = array(
			'id'                => $suggestion_id,
			'_priority'         => 1,
			'_type'             => ExtensionSuggestions::TYPE_PSP,
			'title'             => 'Suggestion 1',
			'description'       => 'Description 1',
			'plugin'            => array(
				'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
				'slug'  => 'slug1',
			),
			'image'             => 'http://example.com/image1.png',
			'icon'              => 'http://example.com/icon1.png',
			'short_description' => null,
			'links'             => array(
				array(
					'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
					'url'   => 'url1',
				),
			),
			'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
		);
		$this->mock_extension_suggestions
			->expects( $this->once() )
			->method( 'get_by_id' )
			->with( $suggestion_id )
			->willReturn( $suggestion_details );

		update_user_meta(
			$this->store_admin_id,
			Payments::USER_PAYMENTS_NOX_PROFILE_KEY,
			array(
				'something_other' => 'value',
			)
		);

		// Act.
		$result = $this->sut->hide_extension_suggestion( $suggestion_id );

		// Assert.
		$this->assertTrue( $result );
		$user_nox_profile = get_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY, true );
		$this->assertIsArray( $user_nox_profile );
		$this->assertArrayHasKey( 'hidden_suggestions', $user_nox_profile );
		$this->assertIsList( $user_nox_profile['hidden_suggestions'] );
		$this->assertCount( 1, $user_nox_profile['hidden_suggestions'] );
		$this->assertSame( $suggestion_id, $user_nox_profile['hidden_suggestions'][0]['id'] );
		$this->assertArrayHasKey( 'timestamp', $user_nox_profile['hidden_suggestions'][0] );
		// The other profile entries should be kept.
		$this->assertSame( 'value', $user_nox_profile['something_other'] );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY );
	}

	/**
	 * Test hiding a payment extension suggestion when provided with an order map ID.
	 */
	public function test_hide_extension_suggestion_with_order_map_id() {
		// Arrange.
		$suggestion_id      = 'suggestion1';
		$suggestion_details = array(
			'id'                => $suggestion_id,
			'_priority'         => 1,
			'_type'             => ExtensionSuggestions::TYPE_PSP,
			'title'             => 'Suggestion 1',
			'description'       => 'Description 1',
			'plugin'            => array(
				'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
				'slug'  => 'slug1',
			),
			'image'             => 'http://example.com/image1.png',
			'icon'              => 'http://example.com/icon1.png',
			'short_description' => null,
			'links'             => array(
				array(
					'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
					'url'   => 'url1',
				),
			),
			'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
		);
		$this->mock_extension_suggestions
			->expects( $this->once() )
			->method( 'get_by_id' )
			->with( $suggestion_id )
			->willReturn( $suggestion_details );

		$order_map_id = PaymentProviders::SUGGESTION_ORDERING_PREFIX . $suggestion_id;

		update_user_meta(
			$this->store_admin_id,
			Payments::USER_PAYMENTS_NOX_PROFILE_KEY,
			array(
				'something_other' => 'value',
			)
		);

		// Act.
		$result = $this->sut->hide_extension_suggestion( $order_map_id );

		// Assert.
		$this->assertTrue( $result );
		$user_nox_profile = get_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY, true );
		$this->assertIsArray( $user_nox_profile );
		$this->assertArrayHasKey( 'hidden_suggestions', $user_nox_profile );
		$this->assertIsList( $user_nox_profile['hidden_suggestions'] );
		$this->assertCount( 1, $user_nox_profile['hidden_suggestions'] );
		// The suggestion ID should be stored, not the order map ID.
		$this->assertSame( $suggestion_id, $user_nox_profile['hidden_suggestions'][0]['id'] );
		$this->assertArrayHasKey( 'timestamp', $user_nox_profile['hidden_suggestions'][0] );
		// The other profile entries should be kept.
		$this->assertSame( 'value', $user_nox_profile['something_other'] );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY );
	}

	/**
	 * Test hiding a payment extension suggestion that is already hidden.
	 */
	public function test_hide_extension_suggestion_already_hidden() {
		// Arrange.
		$suggestion_id  = 'suggestion1';
		$hide_timestamp = 123;

		$suggestion_details = array(
			'id'                => $suggestion_id,
			'_priority'         => 1,
			'_type'             => ExtensionSuggestions::TYPE_PSP,
			'title'             => 'Suggestion 1',
			'description'       => 'Description 1',
			'plugin'            => array(
				'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
				'slug'  => 'slug1',
			),
			'image'             => 'http://example.com/image1.png',
			'icon'              => 'http://example.com/icon1.png',
			'short_description' => null,
			'links'             => array(
				array(
					'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
					'url'   => 'url1',
				),
			),
			'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
		);
		$this->mock_extension_suggestions
			->expects( $this->once() )
			->method( 'get_by_id' )
			->with( $suggestion_id )
			->willReturn( $suggestion_details );

		update_user_meta(
			$this->store_admin_id,
			Payments::USER_PAYMENTS_NOX_PROFILE_KEY,
			array(
				'something_other'    => 'value',
				'hidden_suggestions' => array(
					array(
						'id'        => $suggestion_id,
						'timestamp' => $hide_timestamp, // This should not be updated.
					),
				),
			)
		);

		// Act.
		$result = $this->sut->hide_extension_suggestion( $suggestion_id );

		// Assert.
		$this->assertTrue( $result );
		$user_nox_profile = get_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY, true );
		$this->assertIsArray( $user_nox_profile );
		$this->assertArrayHasKey( 'hidden_suggestions', $user_nox_profile );
		$this->assertIsList( $user_nox_profile['hidden_suggestions'] );
		$this->assertCount( 1, $user_nox_profile['hidden_suggestions'] );
		$this->assertSame( $suggestion_id, $user_nox_profile['hidden_suggestions'][0]['id'] );
		$this->assertArrayHasKey( 'timestamp', $user_nox_profile['hidden_suggestions'][0] );
		$this->assertSame( $hide_timestamp, $user_nox_profile['hidden_suggestions'][0]['timestamp'] );
		// The other profile entries should be kept.
		$this->assertSame( 'value', $user_nox_profile['something_other'] );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY );
	}

	/**
	 * Test hiding a payment extension suggestion that is already hidden when provided with an order map ID.
	 */
	public function test_hide_extension_suggestion_already_hidden_with_order_map_id() {
		// Arrange.
		$suggestion_id  = 'suggestion1';
		$order_map_id   = PaymentProviders::SUGGESTION_ORDERING_PREFIX . $suggestion_id;
		$hide_timestamp = 123;

		$suggestion_details = array(
			'id'                => $suggestion_id,
			'_priority'         => 1,
			'_type'             => ExtensionSuggestions::TYPE_PSP,
			'title'             => 'Suggestion 1',
			'description'       => 'Description 1',
			'plugin'            => array(
				'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
				'slug'  => 'slug1',
			),
			'image'             => 'http://example.com/image1.png',
			'icon'              => 'http://example.com/icon1.png',
			'short_description' => null,
			'links'             => array(
				array(
					'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
					'url'   => 'url1',
				),
			),
			'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
		);
		$this->mock_extension_suggestions
			->expects( $this->once() )
			->method( 'get_by_id' )
			->with( $suggestion_id )
			->willReturn( $suggestion_details );

		update_user_meta(
			$this->store_admin_id,
			Payments::USER_PAYMENTS_NOX_PROFILE_KEY,
			array(
				'something_other'    => 'value',
				'hidden_suggestions' => array(
					array(
						'id'        => $suggestion_id,
						'timestamp' => $hide_timestamp, // This should not be updated.
					),
				),
			)
		);

		// Act.
		$result = $this->sut->hide_extension_suggestion( $order_map_id );

		// Assert.
		$this->assertTrue( $result );
		$user_nox_profile = get_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY, true );
		$this->assertIsArray( $user_nox_profile );
		$this->assertArrayHasKey( 'hidden_suggestions', $user_nox_profile );
		$this->assertIsList( $user_nox_profile['hidden_suggestions'] );
		$this->assertCount( 1, $user_nox_profile['hidden_suggestions'] );
		$this->assertSame( $suggestion_id, $user_nox_profile['hidden_suggestions'][0]['id'] );
		$this->assertArrayHasKey( 'timestamp', $user_nox_profile['hidden_suggestions'][0] );
		$this->assertSame( $hide_timestamp, $user_nox_profile['hidden_suggestions'][0]['timestamp'] );
		// The other profile entries should be kept.
		$this->assertSame( 'value', $user_nox_profile['something_other'] );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY );
	}

	/**
	 * Test hiding a payment extension suggestion resulting in failure to update the user meta.
	 */
	public function test_hide_extension_suggestion_failure() {
		// Arrange.
		$suggestion_id      = 'suggestion1';
		$suggestion_details = array(
			'id'                => $suggestion_id,
			'_priority'         => 1,
			'_type'             => ExtensionSuggestions::TYPE_PSP,
			'title'             => 'Suggestion 1',
			'description'       => 'Description 1',
			'plugin'            => array(
				'_type' => ExtensionSuggestions::PLUGIN_TYPE_WPORG,
				'slug'  => 'slug1',
			),
			'image'             => 'http://example.com/image1.png',
			'icon'              => 'http://example.com/icon1.png',
			'short_description' => null,
			'links'             => array(
				array(
					'_type' => ExtensionSuggestions::LINK_TYPE_ABOUT,
					'url'   => 'url1',
				),
			),
			'tags'              => array( 'tag1', ExtensionSuggestions::TAG_PREFERRED ),
		);
		$this->mock_extension_suggestions
			->expects( $this->once() )
			->method( 'get_by_id' )
			->with( $suggestion_id )
			->willReturn( $suggestion_details );

		update_user_meta(
			$this->store_admin_id,
			Payments::USER_PAYMENTS_NOX_PROFILE_KEY,
			array(
				'something_other'    => 'value',
				'hidden_suggestions' => array(
					array(
						'id'        => 'suggestion2',
						'timestamp' => time(),
					),
				),
			)
		);

		add_filter( 'update_user_metadata', '__return_false' );

		// Act.
		$result = $this->sut->hide_extension_suggestion( $suggestion_id );

		// Assert.
		$this->assertFalse( $result );
		$user_nox_profile = get_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY, true );
		$this->assertIsArray( $user_nox_profile );
		$this->assertArrayHasKey( 'hidden_suggestions', $user_nox_profile );
		$this->assertIsList( $user_nox_profile['hidden_suggestions'] );
		$this->assertCount( 1, $user_nox_profile['hidden_suggestions'] );
		$this->assertSame( 'suggestion2', $user_nox_profile['hidden_suggestions'][0]['id'] );
		// The other profile entries should be kept.
		$this->assertSame( 'value', $user_nox_profile['something_other'] );

		// Clean up.
		remove_filter( 'update_user_metadata', '__return_false' );
		delete_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY );
	}

	/**
	 * Test hiding a payment extension suggestion resulting in an exception when the suggestion can't be found.
	 */
	public function test_hide_extension_suggestion_throws_if_suggestion_not_found() {
		// Arrange.
		$suggestion_id = 'suggestion1';

		$this->mock_extension_suggestions
			->expects( $this->once() )
			->method( 'get_by_id' )
			->with( $suggestion_id )
			->willReturn( null );

		// Assert.
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid suggestion ID.' );

		// Act.
		$result = $this->sut->hide_extension_suggestion( $suggestion_id );
	}

	/**
	 * Test updating the payment providers order map.
	 *
	 * @dataProvider data_provider_test_update_payment_providers_order_map
	 *
	 * @param array $start_order    The starting order map.
	 * @param array $new_order_map  The new order map.
	 * @param array $expected_order The expected order map.
	 * @param array $gateways       The payment gateways to mock.
	 * @param array $suggestions    The extension suggestions to mock.
	 */
	public function test_update_payment_providers_order_map( array $start_order, array $new_order_map, array $expected_order, array $gateways, array $suggestions ) {
		// Arrange.
		$this->mock_payment_gateways( $gateways );

		// Mock getting suggestions by plugin slug.
		$this->mock_extension_suggestions
			->expects( $this->any() )
			->method( 'get_by_plugin_slug' )
			->willReturnCallback(
				function ( $plugin_slug ) use ( $suggestions ) {
					foreach ( $suggestions as $suggestion ) {
						if ( $suggestion['plugin']['slug'] === $plugin_slug ) {
							return $suggestion;
						}
					}
					return null;
				}
			);
		// Mock getting suggestions by id.
		$this->mock_extension_suggestions
			->expects( $this->any() )
			->method( 'get_by_id' )
			->willReturnCallback(
				function ( $id ) use ( $suggestions ) {
					foreach ( $suggestions as $suggestion ) {
						if ( $suggestion['id'] === $id ) {
							return $suggestion;
						}
					}
					return null;
				}
			);

		// Set the starting order map.
		$start_order_map = array_flip( $start_order );
		update_option( PaymentProviders::PROVIDERS_ORDER_OPTION, $start_order_map );

		// Act.
		$result = $this->sut->update_payment_providers_order_map( $new_order_map );

		// Assert.
		$expected_order_map   = array_flip( $expected_order );
		$expect_option_update = $start_order_map !== $expected_order_map;
		$this->assertSame(
			$expect_option_update,
			$result,
			$expect_option_update ? 'Expected order map option to BE updated but it was not.' : 'Expected order map option to NOT BE updated but it was.'
		);
		$this->assertSame( $expected_order_map, get_option( PaymentProviders::PROVIDERS_ORDER_OPTION ) );

		// Clean up.
		$this->unmock_payment_gateways();
	}

	/**
	 * Mock a set of payment gateways.
	 *
	 * @param array $gateways The list of gateway details keyed by the gateway id.
	 * @param bool  $append   Whether to append the gateways to the existing ones.
	 *                        Defaults to false, which means the existing gateways will be removed.
	 */
	protected function mock_payment_gateways( array $gateways, bool $append = false ) {
		if ( ! empty( $gateways ) ) {
			add_action(
				'wc_payment_gateways_initialized',
				function ( \WC_Payment_Gateways $wc_payment_gateways ) use ( $gateways, $append ) {
					if ( ! $append ) {
						$wc_payment_gateways->payment_gateways = array();
					}

					$order = 99999;
					foreach ( $gateways as $gateway_id => $gateway_data ) {
						$wc_payment_gateways->payment_gateways[ $order++ ] = new FakePaymentGateway( $gateway_id, $gateway_data );
					}
				},
				100
			);
		} else {
			// If there are no gateways, just reset the gateways.
			add_action(
				'wc_payment_gateways_initialized',
				function ( \WC_Payment_Gateways $wc_payment_gateways ) {
					$wc_payment_gateways->payment_gateways = array();
				},
				100
			);
		}

		WC()->payment_gateways()->init();

		$this->sut->reset_memo();
	}

	/**
	 * Unmock the payment gateways.
	 */
	protected function unmock_payment_gateways() {
		remove_all_actions( 'wc_payment_gateways_initialized' );
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();

		$this->sut->reset_memo();
	}

	/**
	 * Data provider for the test_update_payment_providers_order_map.
	 *
	 * @return array
	 */
	public function data_provider_test_update_payment_providers_order_map(): array {
		$gateways = array(
			'gateway1'   => array(
				'enabled'     => false,
				'plugin_slug' => 'plugin1',
			),
			'gateway2'   => array(
				'enabled'     => false,
				'plugin_slug' => 'plugin2',
			),
			'gateway3_0' => array(
				'enabled'     => false,
				'plugin_slug' => 'plugin3',
			), // Same plugin slug.
			'gateway3_1' => array(
				'enabled'     => false,
				'plugin_slug' => 'plugin3',
			), // Same plugin slug.
		);

		$offline_payment_methods_gateways = array(
			WC_Gateway_BACS::ID   => array(
				'enabled'     => false,
				'plugin_slug' => 'woocommerce',
			),
			WC_Gateway_Cheque::ID => array(
				'enabled'     => false,
				'plugin_slug' => 'woocommerce',
			),
			WC_Gateway_COD::ID    => array(
				'enabled'     => false,
				'plugin_slug' => 'woocommerce',
			),
		);

		$suggestions = array(
			array(
				'id'        => 'suggestion1',
				'_type'     => ExtensionSuggestions::TYPE_PSP,
				'_priority' => 0,
				'plugin'    => array( 'slug' => 'plugin1' ),
			),
			array(
				'id'        => 'suggestion3',
				'_type'     => ExtensionSuggestions::TYPE_PSP,
				'_priority' => 1,
				'plugin'    => array( 'slug' => 'plugin3' ),
			),
			array(
				'id'        => 'suggestion-other',
				'_type'     => ExtensionSuggestions::TYPE_PSP,
				'_priority' => 2,
				'plugin'    => array( 'slug' => 'plugin-other' ),
			),
		);

		return array(
			'empty start, no ordering - no gateways | no offline PMs | no suggestions' => array(
				array(),
				array(),
				array(),
				array(),
				array(),
			),
			'empty start, no ordering - gateways | no offline PMs | no suggestions' => array(
				array(),
				array(),
				array(
					'gateway1',
					'gateway2',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways,
				array(),
			),
			'empty start, no ordering #2 - gateways | no offline PMs | no suggestions' => array(
				array(),
				array(
					'gateway1'       => null, // These should all be ignored.
					'gateway2'       => 1.2,
					'gateway3_0'     => 'bogus',
					'gateway3_1'     => false,
					'something'      => array( '0' ),
					'something_else' => new \stdClass(),
				),
				array(
					'gateway1',
					'gateway2',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways,
				array(),
			),
			'empty start, no ordering - no gateways | offline PMs | no suggestions' => array(
				array(),
				array(),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$offline_payment_methods_gateways,
				array(),
			),
			'empty start, no ordering - gateways | offline PMs | no suggestions' => array(
				array(),
				array(),
				array(
					'gateway1',
					'gateway2',
					'gateway3_0',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'empty start, no ordering - offline PMs | gateways | no suggestions' => array(
				array(),
				array(),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
					'gateway1',
					'gateway2',
					'gateway3_0',
					'gateway3_1',
				),
				$offline_payment_methods_gateways + $gateways,
				array(),
			),
			'empty start, no ordering - gateways | no offline PMs | suggestions' => array(
				array(),
				array(),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways,
				$suggestions,
			),
			'empty start, no ordering - gateways | offline PMs | suggestions'    => array(
				array(),
				array(),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, no ordering - offline PMs | gateways | suggestions'    => array(
				array(),
				array(),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
				),
				$offline_payment_methods_gateways + $gateways,
				$suggestions,
			),
			'empty start, move offline PMs - gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					WC_Gateway_Cheque::ID => 1,
					WC_Gateway_BACS::ID   => 2,
				),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move all offline PMs - gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					WC_Gateway_COD::ID    => 0,
					WC_Gateway_Cheque::ID => 1,
					WC_Gateway_BACS::ID   => 2,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move offline PMs group - gateways | offline PMs | no suggestions'    => array(
				array(),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 0,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
					'gateway1',
					'gateway2',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'empty start, move offline PMs group - gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 0,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move offline PMs group - no gateways | offline PMs | no suggestions'    => array(
				array(),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 10,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$offline_payment_methods_gateways,
				array(),
			),
			'empty start, move offline PMs group - no gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 10,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move offline PM - no gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					WC_Gateway_COD::ID => 0,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
				),
				$offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move all offline PMs - no gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					WC_Gateway_COD::ID    => 0,
					WC_Gateway_Cheque::ID => 1,
					WC_Gateway_BACS::ID   => 2,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
				),
				$offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move gateway - gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					'gateway3_0' => 3,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move gateways - gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					'gateway1'   => 2,
					'gateway3_0' => 3,
				),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move gateways #2 - gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					'gateway1' => 2,
					'gateway2' => 3,
				),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move gateways #3 - gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					'gateway1'   => 2,
					'gateway3_1' => 3,
				),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'_wc_pes_suggestion3',
					'gateway3_1',
					'gateway2',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move gateways and offline PMs group - gateways | offline PMs | suggestions'    => array(
				array(),
				array(
					'gateway1'   => 2,
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 3,
					'gateway3_1' => 4,
				),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
					'_wc_pes_suggestion3',
					'gateway3_1',
					'gateway2',
					'gateway3_0',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'empty start, move gateway - offline PMs | gateways | suggestions'    => array(
				array(),
				array(
					'gateway3_0' => 3,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'gateway3_1',
				),
				$offline_payment_methods_gateways + $gateways,
				$suggestions,
			),
			'empty start, move gateways - offline PMs | gateways | suggestions'    => array(
				array(),
				array(
					'gateway3_0' => 3,
					'gateway1'   => 4,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'_wc_pes_suggestion1',
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
					'gateway2',
					'gateway3_1',
				),
				$offline_payment_methods_gateways + $gateways,
				$suggestions,
			),
			'empty start, move gateways #2 - offline PMs | gateways | suggestions'    => array(
				array(),
				array(
					'gateway3_0' => 3,
					'gateway2'   => 4,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway3_1',
				),
				$offline_payment_methods_gateways + $gateways,
				$suggestions,
			),
			'empty start, move gateways #3 - offline PMs | gateways | suggestions'    => array(
				array(),
				array(
					'gateway3_0' => 3,
					'gateway3_1' => 4,
					'gateway2'   => 5,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$offline_payment_methods_gateways + $gateways,
				$suggestions,
			),
			'empty start, move gateways and offline PMs group - offline PMs | gateways | suggestions'    => array(
				array(),
				array(
					'gateway3_0' => 3,
					'gateway3_1' => 4,
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 5,
					'gateway2'   => 6,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
					'gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$offline_payment_methods_gateways + $gateways,
				$suggestions,
			),
			'legacy order, no ordering - no gateways | offline PMs | no suggestions'    => array(
				array(
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				array(),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$offline_payment_methods_gateways,
				array(),
			),
			'legacy order with non-existent gateways, no ordering - no gateways | offline PMs | no suggestions'    => array(
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				array(),
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$offline_payment_methods_gateways,
				array(),
			),
			'legacy order, no ordering - gateways | offline PMs | no suggestions'    => array(
				array(
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway1',
					'gateway2',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'legacy order, no ordering #2 - gateways | offline PMs | no suggestions'    => array(
				array(
					'gateway1',
					'gateway2',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(),
				array(
					'gateway1',
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'legacy order, no ordering #3 - gateways | offline PMs | no suggestions'    => array(
				array(
					'gateway1',
					WC_Gateway_BACS::ID,
					'gateway2',
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(),
				array(
					'gateway1',
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'legacy order, no ordering #4 - gateways | offline PMs | no suggestions'    => array(
				array(
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
				),
				array(),
				array(
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'legacy order, no ordering #5 - gateways | offline PMs | no suggestions'    => array(
				array(
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_0',
					'gateway3_1',
				),
				array(),
				array(
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'legacy order, no ordering #6 - gateways | offline PMs | no suggestions'    => array(
				array(
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					'gateway2',
					'gateway3_0',
					WC_Gateway_Cheque::ID,
					'gateway3_1',
				),
				array(),
				array(
					'gateway1',
					'gateway2',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'legacy order, no ordering #7 - gateways | offline PMs | no suggestions'    => array(
				array(
					'gateway1',
					'gateway2',
					'gateway3_0',
					'gateway3_1',
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
				),
				array(),
				array(
					'gateway1',
					'gateway2',
					'gateway3_0',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'legacy order, no ordering #8 - gateways | offline PMs | no suggestions'    => array(
				array(
					'gateway2',
					'gateway3_0',
					'gateway1',
					'gateway3_1',
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
				),
				array(),
				array(
					'gateway2',
					'gateway3_0',
					'gateway1',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'legacy order, no ordering - gateways | offline PMs | suggestions'    => array(
				array(
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, no ordering #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway1',
					'gateway2',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, no ordering #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway1',
					'gateway2',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway3_0',
				),
				array(),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, no ordering #4 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway1',
					'gateway2',
					'gateway3_0',
					'gateway3_1',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, no ordering #5 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway2',
					'gateway3_0',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				array(),
				array(
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, no ordering #6 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				array(),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order with non-existent, no ordering - gateways | offline PMs | no suggestions'    => array(
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
				),
				array(),
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					'gateway1',
					'gateway2',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'legacy order with both existent and non-existent, no ordering - gateways | offline PMs | no suggestions'    => array(
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					'gateway1',
					'gateway2',
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
				),
				array(),
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					'gateway1',
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				array(),
			),
			'legacy order with non-existent, no ordering - gateways | offline PMs | suggestions'    => array(
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
				),
				array(),
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order with both existent and non-existent, no ordering - gateways | offline PMs | suggestions'    => array(
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					'gateway1',
					'gateway2',
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
				),
				array(),
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order with both existent and non-existent, no ordering #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					'gateway1',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
				),
				array(),
				array(
					'non_existent_gateway1',
					'non_existent_gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order with both existent and non-existent, no ordering #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway1',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					'non_existent_gateway1',
					'non_existent_gateway2',
				),
				array(),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					'non_existent_gateway1',
					'non_existent_gateway2',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order with both existent and non-existent, no ordering #4 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway1',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'non_existent_gateway1',
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'non_existent_gateway2',
					WC_Gateway_BACS::ID,
				),
				array(),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'non_existent_gateway1',
					'non_existent_gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, move gateways - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				array(
					'gateway1'   => 2,
					'gateway3_0' => 3,
				),
				array(
					'gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
					'_wc_pes_suggestion3',
					'gateway3_1',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, move gateway - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				array(
					'gateway1' => 1,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, move gateway #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				array(
					'gateway3_1' => 0,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_1',
					'gateway3_0',
					'gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, move offline PM - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(
					WC_Gateway_BACS::ID => 0,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, move offline PM #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(
					WC_Gateway_COD::ID => 0,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, move offline PM #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(
					WC_Gateway_Cheque::ID => 1,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, move all offline PMs - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(
					WC_Gateway_COD::ID    => 0,
					WC_Gateway_Cheque::ID => 1,
					WC_Gateway_BACS::ID   => 2,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, move offline PMs group - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 0,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'legacy order, move offline PMs group #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'gateway1',
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 1,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, no ordering - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order with non-existent payment gateways, no ordering - gateways | offline PMs | suggestions'    => array(
				array(
					'non_existent_gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'non_existent_gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(),
				array(
					'non_existent_gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'non_existent_gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order with non-existent payment gateways #2, no ordering - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_non_existent_gateway1',
					'non_existent_gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_non_existent_gateway2',
					'non_existent_gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(),
				array(
					'_wc_pes_non_existent_gateway1',
					'non_existent_gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_non_existent_gateway2',
					'non_existent_gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order with suggestions but no matching gateways, no ordering - gateways | offline PMs | suggestions'       => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway2',
				),
				array(),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0', // Suggestion matching gateways (via the plugin slug) are added after their suggestion, in order.
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway1', // Gateway added after its suggestion.
					'gateway2',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order with suggestions but no matching gateways, ordering #1 - gateways | offline PMs | suggestions'       => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway2',
				),
				array(
					'gateway2' => 0,
				),
				array(
					'gateway2',
					'_wc_pes_suggestion3',
					'gateway3_0', // Suggestion matching gateways (via the plugin slug) are added after their suggestion, in order.
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway1', // Gateway added after its suggestion.
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order with suggestions but no matching gateways, ordering #2 - gateways | offline PMs | suggestions'       => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway2',
				),
				array(
					'gateway2' => 0,
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 1,
				),
				array(
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion3',
					'gateway3_0', // Suggestion matching gateways (via the plugin slug) are added after their suggestion, in order.
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1', // Gateway added after its suggestion.
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order with suggestions but no matching gateways, ordering #3 - gateways | offline PMs | suggestions'       => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway2',
				),
				array(
					WC_Gateway_BACS::ID   => 0, // Special offline PMs normalized order map - no-op.
					WC_Gateway_COD::ID    => 1,
					WC_Gateway_Cheque::ID => 2,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0', // Suggestion matching gateways (via the plugin slug) are added after their suggestion, in order.
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // no-op.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway1', // Gateway added after its suggestion.
					'gateway2',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order with suggestions but no matching gateways, ordering #4 - gateways | offline PMs | suggestions'       => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway2',
				),
				array(
					WC_Gateway_COD::ID    => 0, // Special offline PMs normalized order map.
					WC_Gateway_BACS::ID   => 1,
					WC_Gateway_Cheque::ID => 2,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0', // Suggestion matching gateways (via the plugin slug) are added after their suggestion, in order.
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway1', // Gateway added after its suggestion.
					'gateway2',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order with suggestions but no matching gateways, ordering #5 - gateways | offline PMs | suggestions'       => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // This has order 2.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway2',
				),
				array(
					WC_Gateway_COD::ID    => 2, // Special offline PMs non-normalized order map.
					WC_Gateway_BACS::ID   => 3,
					WC_Gateway_Cheque::ID => 4,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0', // Suggestion matching gateways (via the plugin slug) are added after their suggestion, in order.
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway1', // Gateway added after its suggestion.
					'gateway2',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order with non-existent payment gateways, move payment gateways - gateways | offline PMs | suggestions'    => array(
				array(
					'non_existent_gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_non_existent_gateway2',
					'non_existent_gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'gateway3_0' => 0,
					'gateway1'   => 1,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'_wc_pes_suggestion1',
					'gateway1',
					'non_existent_gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_non_existent_gateway2',
					'non_existent_gateway2',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order with non-existent payment gateways, move payment gateway - gateways | offline PMs | suggestions'    => array(
				array(
					'non_existent_gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_non_existent_gateway2',
					'non_existent_gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'gateway1' => 1,
				),
				array(
					'non_existent_gateway1',
					'_wc_pes_suggestion1',
					'gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_non_existent_gateway2',
					'non_existent_gateway2',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move payment gateway - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'gateway1' => 1,
				),
				array(
					'_wc_pes_suggestion1',
					'gateway1',
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move payment gateway #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'gateway2' => 2,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move payment gateway #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // This has order 3.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'gateway2' => 3,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2', // Because the offline PMs group was present, the offline PMs stuck with it.
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move payment gateway lower #1 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1', // This has order 7.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'gateway2' => 7,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway3_1',
					'gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move payment gateway lower #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1', // This has order 8.
					'gateway1',
				),
				array(
					'gateway2' => 8,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway3_1',
					'gateway2',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move payment gateway lower #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1', // This has order 9.
				),
				array(
					'gateway2' => 9,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
					'gateway2',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PMs group on itself - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // This has order 3.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 3,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PMs group on itself #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID, // This has order 5.
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 5,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PMs group on next - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2', // This has order 6.
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 6,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PMs group - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1', // This has order 7.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 7,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PMs group #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1', // This has order 8.
					'gateway1', // This should remain in place because registered PGs have more power than suggestions.
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 8,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1', // Because the corresponding PG remained in place, this stuck with it.
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PMs group #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1', // This has order 9.
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 9,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PMs group #4 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP => 10,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PM - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // This had order 3.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					WC_Gateway_BACS::ID => 3,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PM #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID, // This had order 4.
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					WC_Gateway_BACS::ID => 4,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_BACS::ID, // Because the offline PG was present, the reordering took place only inside the offline PMs group.
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PM #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID, // This had order 5.
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					WC_Gateway_BACS::ID => 5,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID, // Because the offline PG was present, the reordering took place only inside the offline PMs group.
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move offline PM #4 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2', // This has order 6.
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					WC_Gateway_BACS::ID => 6,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID, // Because the offline PG was present, the reordering took place only inside the offline PMs group.
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move only offline PMs #1 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // This has order 3.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					WC_Gateway_BACS::ID   => 4,
					WC_Gateway_COD::ID    => 5,
					WC_Gateway_Cheque::ID => 6,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // No change here because the ordering was done inside the offline PMs group.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move only offline PMs #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					WC_Gateway_BACS::ID   => 4, // Sorting doesn't matter.
					WC_Gateway_COD::ID    => 3,
					WC_Gateway_Cheque::ID => 5,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move only offline PMs #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					WC_Gateway_BACS::ID   => 5,
					WC_Gateway_COD::ID    => 3,
					WC_Gateway_Cheque::ID => 4,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move only offline PMs normalized #1 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // This has order 3.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					WC_Gateway_BACS::ID   => 0,
					WC_Gateway_COD::ID    => 1,
					WC_Gateway_Cheque::ID => 2,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move only offline PMs normalized #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // This has order 3.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					WC_Gateway_COD::ID    => 0,
					WC_Gateway_BACS::ID   => 1,
					WC_Gateway_Cheque::ID => 2,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_COD::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move only offline PMs normalized #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // This has order 3.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					WC_Gateway_Cheque::ID => 0,
					WC_Gateway_BACS::ID   => 1,
					WC_Gateway_COD::ID    => 2,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_Cheque::ID,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion #1 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion-other', // This has order 8.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'_wc_pes_suggestion-other' => 7,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'_wc_pes_suggestion-other',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion-other', // This has order 8.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'_wc_pes_suggestion-other' => 6,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion-other',
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion-other', // This has order 8.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'_wc_pes_suggestion-other' => 5,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion-other', // Because we have an offline PMs group, the PMs stuck with it.
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion #4 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion-other', // This has order 8.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'_wc_pes_suggestion-other' => 2,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					'_wc_pes_suggestion-other',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion #5 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion-other', // This has order 8.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'_wc_pes_suggestion-other' => 1,
				),
				array(
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion #6 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion-other', // This has order 8.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'_wc_pes_suggestion-other' => 0,
				),
				array(
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion lower #1 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion-other', // This has order 8.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'_wc_pes_suggestion-other' => 9,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion1', // This has a matching PG, so it stuck with it.
					'gateway1',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion lower #2 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion-other', // This has order 8.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'_wc_pes_suggestion-other' => 10,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
					'_wc_pes_suggestion-other',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion lower #3 - gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion-other', // This has order 8.
					'_wc_pes_suggestion1',
					'gateway1',
				),
				array(
					'_wc_pes_suggestion-other' => 11,
				),
				array(
					'_wc_pes_suggestion3',
					'gateway3_0',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'gateway2',
					'gateway3_1',
					'_wc_pes_suggestion1',
					'gateway1',
					'_wc_pes_suggestion-other',
				),
				$gateways + $offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion #1 - no gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion1',
				),
				array(
					'_wc_pes_suggestion-other' => 1,
				),
				array(
					'_wc_pes_suggestion3',
					'_wc_pes_suggestion-other',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
				),
				$offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion #2 - no gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion1',
				),
				array(
					'_wc_pes_suggestion-other' => 1,
				),
				array(
					'_wc_pes_suggestion3',
					'_wc_pes_suggestion-other',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
				),
				$offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion #3 - no gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID, // This has order 2.
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion1',
				),
				array(
					'_wc_pes_suggestion-other' => 2,
				),
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion1',
				),
				$offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion lower #1 - no gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion1',
				),
				array(
					'_wc_pes_suggestion3' => 1,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion3',
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion1',
				),
				$offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion lower #2 - no gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion1', // This has order 6.
				),
				array(
					'_wc_pes_suggestion-other' => 6,
				),
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion1',
					'_wc_pes_suggestion-other',
				),
				$offline_payment_methods_gateways,
				$suggestions,
			),
			'new order, move suggestion lower #3 - no gateways | offline PMs | suggestions'    => array(
				array(
					'_wc_pes_suggestion3',
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID, // This has order 3.
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion1',
				),
				array(
					'_wc_pes_suggestion3' => 3,
				),
				array(
					PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
					WC_Gateway_BACS::ID,
					WC_Gateway_COD::ID,
					WC_Gateway_Cheque::ID,
					'_wc_pes_suggestion3', // Because the suggestion, jumped the offline PMs group, all the offline PMs jumped it also.
					'_wc_pes_suggestion-other',
					'_wc_pes_suggestion1',
				),
				$offline_payment_methods_gateways,
				$suggestions,
			),
		);
	}

	/**
	 * Load the WC core PayPal gateway but not enable it.
	 *
	 * @return void
	 */
	private function load_core_paypal_pg() {
		// Make sure the WC core PayPal gateway is loaded.
		update_option(
			'woocommerce_paypal_settings',
			array(
				'_should_load' => 'yes',
				'enabled'      => 'no',
			)
		);
		// Make sure the store currency is supported by the gateway.
		update_option( 'woocommerce_currency', 'USD' );
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();

		// Reset the controller memo to pick up the new gateway details.
		$this->sut->reset_memo();
	}

	/**
	 * Enable the WC core PayPal gateway.
	 *
	 * @return void
	 */
	private function enable_core_paypal_pg() {
		// Enable the WC core PayPal gateway.
		update_option(
			'woocommerce_paypal_settings',
			array(
				'_should_load' => 'yes',
				'enabled'      => 'yes',
			)
		);
		// Make sure the store currency is supported by the gateway.
		update_option( 'woocommerce_currency', 'USD' );
		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();

		// Reset the controller memo to pick up the new gateway details.
		$this->sut->reset_memo();
	}
}
