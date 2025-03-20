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
 * Payments settings service test.
 *
 * @class Payments
 */
class PaymentsTest extends WC_Unit_Test_Case {

	/**
	 * @var Payments
	 */
	protected $sut;

	/**
	 * @var PaymentProviders|MockObject
	 */
	protected $mock_providers;

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

		$this->mock_providers = $this->getMockBuilder( PaymentProviders::class )
									->disableOriginalConstructor()
									->onlyMethods(
										array(
											'get_payment_gateways',
											'get_extension_suggestions',
											'get_extension_suggestion_categories',
											'hide_extension_suggestion',
											'get_order_map',
											'save_order_map',
											'update_payment_providers_order_map',
											'enhance_order_map',
										)
									)
									->getMock();

		$this->mock_extension_suggestions = $this->getMockBuilder( PaymentExtensionSuggestions::class )
			->disableOriginalConstructor()
			->getMock();

		$this->mock_providers->init( $this->mock_extension_suggestions );

		$this->sut = new Payments();
		$this->sut->init( $this->mock_providers, $this->mock_extension_suggestions );
	}

	/**
	 * Test getting payment providers with no gateways or suggestions.
	 */
	public function test_get_payment_providers_no_gateways_no_suggestions() {
		// Arrange.
		$location = 'US';

		$this->mock_providers
			->expects( $this->atLeastOnce() )
			->method( 'get_payment_gateways' )
			->willReturn( array() );

		$this->mock_providers
			->expects( $this->atLeastOnce() )
			->method( 'get_extension_suggestions' )
			->with( $location )
			->willReturn( array() );

		// Act.
		$data = $this->sut->get_payment_providers( 'US' );

		// Assert.
		$this->assertCount( 0, $data );
	}

	/**
	 * Test getting payment providers with gateways but no suggestions.
	 */
	public function test_get_payment_providers_only_gateways_no_suggestions() {
		// Arrange.
		$location = 'US';

		// All are WooCommerce core gateways.
		$gateways = array(
			new FakePaymentGateway( WC_Gateway_Paypal::ID, array( 'plugin_slug' => 'woocommerce' ) ),
			// The offline PMs.
			new FakePaymentGateway( WC_Gateway_BACS::ID, array( 'plugin_slug' => 'woocommerce' ) ),
			new FakePaymentGateway( WC_Gateway_Cheque::ID, array( 'plugin_slug' => 'woocommerce' ) ),
			new FakePaymentGateway( WC_Gateway_COD::ID, array( 'plugin_slug' => 'woocommerce' ) ),
		);
		$this->mock_providers
			->expects( $this->atLeastOnce() )
			->method( 'get_payment_gateways' )
			->willReturn( $gateways );

		$this->mock_providers
			->expects( $this->any() )
			->method( 'get_order_map' )
			->willReturn( array() ); // No preexisting order map.
		$this->mock_providers
			->expects( $this->any() )
			->method( 'enhance_order_map' )
			->willReturn(
				array_flip(
					array(
						WC_Gateway_Paypal::ID,
						PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
						WC_Gateway_BACS::ID,
						WC_Gateway_Cheque::ID,
						WC_Gateway_COD::ID,
					)
				)
			);

		$base_suggestions = array();
		$this->mock_providers
			->expects( $this->once() )
			->method( 'get_extension_suggestions' )
			->with( $location )
			->willReturn( $base_suggestions );

		// Act.
		$data = $this->sut->get_payment_providers( 'US' );

		// We have the PayPal gateway, the 3 offline payment methods and their group entry.
		$this->assertCount( 5, $data );
		// Because the core registers the PayPal PG after the offline PMs, the order we expect is this.
		$this->assertSame(
			array( WC_Gateway_Paypal::ID, PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP, WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID, WC_Gateway_COD::ID ),
			array_column( $data, 'id' )
		);

		// Assert that the PayPal gateway has all the details.
		$gateway = $data[0];
		$this->assertArrayHasKey( 'id', $gateway, 'Gateway `id` entry is missing' );
		$this->assertArrayHasKey( '_order', $gateway, 'Gateway `_order` entry is missing' );
		$this->assertArrayHasKey( '_type', $gateway, 'Gateway `_type` entry is missing' );
		$this->assertEquals( PaymentProviders::TYPE_GATEWAY, $gateway['_type'], 'Gateway `_type` entry is not `' . PaymentProviders::TYPE_GATEWAY . '`' );
		$this->assertArrayHasKey( 'title', $gateway, 'Gateway `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $gateway, 'Gateway `description` entry is missing' );
		$this->assertArrayHasKey( 'supports', $gateway, 'Gateway `supports` entry is missing' );
		$this->assertIsList( $gateway['supports'], 'Gateway `supports` entry is not a list' );
		$this->assertArrayHasKey( 'state', $gateway, 'Gateway `state` entry is missing' );
		$this->assertArrayHasKey( 'enabled', $gateway['state'], 'Gateway `state[enabled]` entry is missing' );
		$this->assertTrue( $gateway['state']['enabled'], 'Gateway `state[enabled]` entry is not `true`' );
		$this->assertArrayHasKey( 'needs_setup', $gateway['state'], 'Gateway `state[needs_setup]` entry is missing' );
		$this->assertArrayHasKey( 'test_mode', $gateway['state'], 'Gateway `state[test_mode]` entry is missing' );
		$this->assertArrayHasKey( 'dev_mode', $gateway['state'], 'Gateway `state[dev_mode]` entry is missing' );
		$this->assertArrayHasKey( 'management', $gateway, 'Gateway `management` entry is missing' );
		$this->assertArrayHasKey( '_links', $gateway['management'], 'Gateway `management[_links]` entry is missing' );
		$this->assertArrayHasKey( 'settings', $gateway['management']['_links'], 'Gateway `management[_links][settings]` entry is missing' );
		$this->assertArrayHasKey( 'links', $gateway, 'Gateway `links` entry is missing' );
		$this->assertCount( 1, $gateway['links'] );
		$this->assertArrayHasKey( 'plugin', $gateway, 'Gateway `plugin` entry is missing' );
		$this->assertArrayHasKey( 'slug', $gateway['plugin'], 'Gateway `plugin[slug]` entry is missing' );
		$this->assertSame( 'woocommerce', $gateway['plugin']['slug'] );
		$this->assertArrayHasKey( 'file', $gateway['plugin'], 'Gateway `plugin[file]` entry is missing' );
		$this->assertArrayHasKey( 'status', $gateway['plugin'], 'Gateway `plugin[status]` entry is missing' );
		$this->assertSame( PaymentProviders::EXTENSION_ACTIVE, $gateway['plugin']['status'] );

		// Assert that the offline payment methods group has all the details.
		$group = $data[1];
		$this->assertArrayHasKey( 'id', $group, 'Group `id` entry is missing' );
		$this->assertEquals( PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP, $group['id'] );
		$this->assertArrayHasKey( '_order', $group, 'Group `_order` entry is missing' );
		$this->assertIsInteger( $group['_order'], 'Group `_order` entry is not an integer' );
		$this->assertArrayHasKey( '_type', $group, 'Group `_type` entry is missing' );
		$this->assertEquals( PaymentProviders::TYPE_OFFLINE_PMS_GROUP, $group['_type'], 'Group `_type` entry is not `' . PaymentProviders::TYPE_OFFLINE_PMS_GROUP . '`' );
		$this->assertArrayHasKey( 'title', $group, 'Group `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $group, 'Group `description` entry is missing' );
		$this->assertArrayHasKey( 'icon', $group, 'Group `icon` entry is missing' );
		$this->assertArrayHasKey( 'management', $group, 'Gateway `management` entry is missing' );
		$this->assertArrayHasKey( '_links', $group['management'], 'Gateway `management[_links]` entry is missing' );
		$this->assertArrayHasKey( 'settings', $group['management']['_links'], 'Gateway `management[_links][settings]` entry is missing' );
		$this->assertArrayHasKey( 'href', $group['management']['_links']['settings'], 'Gateway `management[_links][settings][href]` entry is missing' );
		$this->assertArrayHasKey( 'plugin', $group, 'Group `plugin` entry is missing' );
		$this->assertArrayHasKey( 'slug', $group['plugin'], 'Group `plugin[slug]` entry is missing' );
		$this->assertSame( 'woocommerce', $group['plugin']['slug'] );
		$this->assertArrayHasKey( 'status', $group['plugin'], 'Group `plugin[status]` entry is missing' );
		$this->assertSame( PaymentProviders::EXTENSION_ACTIVE, $group['plugin']['status'] );

		// Assert that the offline payment methods have all the details.
		$offline_pm = $data[2];
		$this->assertArrayHasKey( 'id', $offline_pm, 'Offline PM `id` entry is missing' );
		$this->assertArrayHasKey( '_order', $offline_pm, 'Offline PM `_order` entry is missing' );
		$this->assertArrayHasKey( '_type', $offline_pm, 'Offline PM `_type` entry is missing' );
		$this->assertEquals( PaymentProviders::TYPE_OFFLINE_PM, $offline_pm['_type'], 'Offline PM `_type` entry is not `' . PaymentProviders::TYPE_OFFLINE_PM . '`' );
		$this->assertArrayHasKey( 'title', $offline_pm, 'Offline PM `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $offline_pm, 'Offline PM `description` entry is missing' );
		$this->assertArrayHasKey( 'icon', $offline_pm, 'Offline PM `icon` entry is missing' );
		$this->assertArrayHasKey( 'management', $offline_pm, 'Offline PM `management` entry is missing' );
		$this->assertArrayHasKey( '_links', $offline_pm['management'], 'Offline PM `management[_links]` entry is missing' );
		$this->assertArrayHasKey( 'settings', $offline_pm['management']['_links'], 'Offline PM `management[_links][settings]` entry is missing' );
		$this->assertArrayHasKey( 'href', $offline_pm['management']['_links']['settings'], 'Offline PM `management[_links][settings][href]` entry is missing' );
		$this->assertArrayHasKey( 'plugin', $offline_pm, 'Offline PM `plugin` entry is missing' );
		$this->assertArrayHasKey( 'slug', $offline_pm['plugin'], 'Offline PM `plugin[slug]` entry is missing' );
		$this->assertSame( 'woocommerce', $offline_pm['plugin']['slug'] );
		$this->assertArrayHasKey( 'status', $offline_pm['plugin'], 'Offline PM `plugin[status]` entry is missing' );
		$this->assertSame( PaymentProviders::EXTENSION_ACTIVE, $offline_pm['plugin']['status'] );
	}

	/**
	 * Test getting payment providers with gateways and suggestions.
	 */
	public function test_get_payment_providers_gateways_and_suggestions() {
		// Arrange.
		$location = 'US';

		// All are WooCommerce core gateways.
		$gateways = array(
			new FakePaymentGateway( WC_Gateway_Paypal::ID, array( 'plugin_slug' => 'woocommerce' ) ),
			// The offline PMs.
			new FakePaymentGateway( WC_Gateway_BACS::ID, array( 'plugin_slug' => 'woocommerce' ) ),
			new FakePaymentGateway( WC_Gateway_Cheque::ID, array( 'plugin_slug' => 'woocommerce' ) ),
			new FakePaymentGateway( WC_Gateway_COD::ID, array( 'plugin_slug' => 'woocommerce' ) ),
		);
		$this->mock_providers
			->expects( $this->atLeastOnce() )
			->method( 'get_payment_gateways' )
			->willReturn( $gateways );

		$this->mock_providers
			->expects( $this->any() )
			->method( 'get_order_map' )
			->willReturn( array() ); // No preexisting order map.
		$this->mock_providers
			->expects( $this->any() )
			->method( 'enhance_order_map' )
			->willReturn(
				array_flip(
					array(
						PaymentProviders::SUGGESTION_ORDERING_PREFIX . 'suggestion1',
						PaymentProviders::SUGGESTION_ORDERING_PREFIX . 'suggestion2',
						WC_Gateway_Paypal::ID,
						PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
						WC_Gateway_BACS::ID,
						WC_Gateway_Cheque::ID,
						WC_Gateway_COD::ID,
					)
				)
			);

		$suggestions = array(
			'preferred' => array(
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
			),
			'other'     => array(
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
			),
		);

		$this->mock_providers
			->expects( $this->atLeastOnce() )
			->method( 'get_extension_suggestions' )
			->with( $location, Payments::SUGGESTIONS_CONTEXT )
			->willReturn( $suggestions );

		// Act.
		$data = $this->sut->get_payment_providers( $location );

		// We have the PayPal gateway, the 3 offline payment methods and their group entry, plus 2 suggestions.
		$this->assertCount( 7, $data );
		// Because the core registers the PayPal PG after the offline PMs, the order we expect is this.
		$this->assertSame(
			array(
				PaymentProviders::SUGGESTION_ORDERING_PREFIX . 'suggestion1',
				PaymentProviders::SUGGESTION_ORDERING_PREFIX . 'suggestion2',
				WC_Gateway_Paypal::ID,
				PaymentProviders::OFFLINE_METHODS_ORDERING_GROUP,
				WC_Gateway_BACS::ID,
				WC_Gateway_Cheque::ID,
				WC_Gateway_COD::ID,
			),
			array_column( $data, 'id' )
		);

		// Assert that the suggestions have all the details.
		$suggestion1 = $data[0];
		$this->assertArrayHasKey( 'id', $suggestion1, 'Provider `id` entry is missing' );
		$this->assertEquals( PaymentProviders::SUGGESTION_ORDERING_PREFIX . 'suggestion1', $suggestion1['id'] );
		$this->assertEquals( 'suggestion1', $suggestion1['_suggestion_id'] );
		$this->assertArrayHasKey( '_order', $suggestion1, 'Provider `_order` entry is missing' );
		$this->assertIsInteger( $suggestion1['_order'], 'Provider `_order` entry is not an integer' );
		$this->assertEquals( 0, $suggestion1['_order'] );
		$this->assertArrayHasKey( '_type', $suggestion1, 'Provider `_type` entry is missing' );
		$this->assertEquals( PaymentProviders::TYPE_SUGGESTION, $suggestion1['_type'] );
		$this->assertArrayHasKey( 'title', $suggestion1, 'Provider `title` entry is missing' );
		$this->assertArrayHasKey( 'description', $suggestion1, 'Provider `description` entry is missing' );
		$this->assertArrayHasKey( 'plugin', $suggestion1, 'Provider `plugin` entry is missing' );
		$this->assertIsArray( $suggestion1['plugin'] );
		$this->assertArrayHasKey( '_type', $suggestion1['plugin'], 'Provider `plugin[_type]` entry is missing' );
		$this->assertArrayHasKey( 'slug', $suggestion1['plugin'], 'Provider `plugin[slug]` entry is missing' );
		$this->assertArrayHasKey( 'icon', $suggestion1, 'Provider `icon` entry is missing' );
		$this->assertArrayHasKey( 'links', $suggestion1, 'Provider `links` entry is missing' );
		$this->assertIsArray( $suggestion1['links'] );
		$this->assertNotEmpty( $suggestion1['links'] );
		$this->assertArrayHasKey( '_type', $suggestion1['links'][0], 'Provider `link[_type]` entry is missing' );
		$this->assertArrayHasKey( 'url', $suggestion1['links'][0], 'Provider `link[url]` entry is missing' );
		$this->assertArrayHasKey( 'tags', $suggestion1, 'Provider `tags` entry is missing' );
		$this->assertIsList( $suggestion1['tags'] );
	}

	/**
	 * Test getting payment extension suggestions.
	 */
	public function test_get_payment_extension_suggestions() {
		// Arrange.
		$location    = 'US';
		$suggestions = array(
			'preferred' => array(),
			'other'     => array(),
		);

		$this->mock_providers
			->expects( $this->once() )
			->method( 'get_extension_suggestions' )
			->with(
				$location,
				Payments::SUGGESTIONS_CONTEXT
			)
			->willReturn( $suggestions );

		// Act.
		$result = $this->sut->get_payment_extension_suggestions( $location );

		// Assert.
		$this->assertSame( $suggestions, $result );
	}

	/**
	 * Test getting payment extension suggestion categories.
	 */
	public function test_get_payment_extension_suggestion_categories() {
		// Arrange.
		$categories = array(
			array(
				'id'          => 'cat1',
				'_priority'   => 10,
				'title'       => 'Title',
				'description' => 'Description',
			),
			array(
				'id'          => 'cat2',
				'_priority'   => 20,
				'title'       => 'Title',
				'description' => 'Description',
			),
		);

		$this->mock_providers
			->expects( $this->once() )
			->method( 'get_extension_suggestion_categories' )
			->willReturn( $categories );

		// Act.
		$result = $this->sut->get_payment_extension_suggestion_categories();

		// Assert.
		$this->assertSame( $categories, $result );
	}

	/**
	 * Test getting the payments settings country.
	 */
	public function test_get_country() {
		// Arrange.
		$country = 'XX';

		update_user_meta(
			$this->store_admin_id,
			Payments::USER_PAYMENTS_NOX_PROFILE_KEY,
			array(
				'business_country_code' => $country,
				'something_other'       => 'value',
			)
		);

		// Act.
		$result = $this->sut->get_country();

		// Assert.
		$this->assertSame( $country, $result );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY );
	}

	/**
	 * Test getting the payments settings country falls back on the WC base location country.
	 */
	public function test_get_country_with_fallback() {
		// Arrange.
		$country = 'LI'; // Liechtenstein.

		update_user_meta(
			$this->store_admin_id,
			Payments::USER_PAYMENTS_NOX_PROFILE_KEY,
			array(
				// business_country_code not set.
				'something_other' => 'value',
			)
		);

		$initial_country = WC()->countries->get_base_country();
		update_option( 'woocommerce_default_country', $country );

		// Act.
		$result = $this->sut->get_country();

		// Assert.
		$this->assertSame( $country, $result );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY );
		update_option( 'woocommerce_default_country', $initial_country );
	}

	/**
	 * Test setting the payments settings country.
	 */
	public function test_set_country() {
		// Arrange.
		$country = 'XX';

		// Act.
		$result = $this->sut->set_country( $country );

		// Assert.
		$this->assertTrue( $result );

		// Check the value was saved.
		$meta = get_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY, true );
		$this->assertIsArray( $meta );
		$this->assertArrayHasKey( 'business_country_code', $meta );
		$this->assertSame( $country, $meta['business_country_code'] );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Payments::USER_PAYMENTS_NOX_PROFILE_KEY );
	}

	/**
	 * Test hiding a payment extension suggestion.
	 */
	public function test_hide_payment_extension_suggestion() {
		// Arrange.
		$suggestion_id = 'suggestion1';

		$this->mock_providers
			->expects( $this->once() )
			->method( 'hide_extension_suggestion' )
			->with( $suggestion_id )
			->willReturn( true );

		// Act.
		$result = $this->sut->hide_payment_extension_suggestion( $suggestion_id );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test dismissing a payment extension suggestion incentive.
	 */
	public function test_dismiss_extension_suggestion_incentive() {
		// Arrange.
		$suggestion_id = 'suggestion1';
		$incentive_id  = 'incentive1';
		$context       = 'context1';

		$this->mock_extension_suggestions
			->expects( $this->once() )
			->method( 'dismiss_incentive' )
			->with( $incentive_id, $suggestion_id, $context )
			->willReturn( true );

		// Act.
		$result = $this->sut->dismiss_extension_suggestion_incentive( $suggestion_id, $incentive_id, $context );

		// Assert.
		$this->assertTrue( $result );
	}

	/**
	 * Test updating the payment providers order map.
	 */
	public function test_update_payment_providers_order_map() {
		// Arrange.
		$order_map = array(
			'gateway1'   => 1,
			'gateway2'   => 2,
			'gateway3_0' => 3,
			'gateway3_1' => 4,
		);
		$this->mock_providers
			->expects( $this->once() )
			->method( 'update_payment_providers_order_map' )
			->willReturn( true );

		// Act.
		$result = $this->sut->update_payment_providers_order_map( $order_map );

		// Assert.
		$this->assertTrue( $result );
	}
}
