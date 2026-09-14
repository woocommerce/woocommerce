<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Providers;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Providers\CustomShippingProvider;
use WC_Unit_Test_Case;

/**
 * Tests for the CustomShippingProvider class.
 */
class CustomShippingProviderTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CustomShippingProvider
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CustomShippingProvider(
			'my-courier',
			'My Local Courier',
			'https://example.com/icon.png',
			'https://example.com/track?id=__PLACEHOLDER__'
		);
	}

	/**
	 * @testdox Should return the correct provider key.
	 */
	public function test_get_key(): void {
		$this->assertSame( 'my-courier', $this->sut->get_key() );
	}

	/**
	 * @testdox Should return the correct provider name.
	 */
	public function test_get_name(): void {
		$this->assertSame( 'My Local Courier', $this->sut->get_name() );
	}

	/**
	 * @testdox Should return the correct icon URL.
	 */
	public function test_get_icon(): void {
		$this->assertSame( 'https://example.com/icon.png', $this->sut->get_icon() );
	}

	/**
	 * @testdox Should replace __PLACEHOLDER__ in tracking URL template with the tracking number.
	 */
	public function test_get_tracking_url_replaces_placeholder(): void {
		$result = $this->sut->get_tracking_url( 'ABC123' );

		$this->assertSame( 'https://example.com/track?id=ABC123', $result );
	}

	/**
	 * @testdox Should URL-encode the tracking number in the tracking URL.
	 */
	public function test_get_tracking_url_encodes_tracking_number(): void {
		$result = $this->sut->get_tracking_url( 'ABC 123&test' );

		$this->assertSame( 'https://example.com/track?id=ABC%20123%26test', $result );
	}

	/**
	 * @testdox Should return empty string when tracking URL template is empty.
	 */
	public function test_get_tracking_url_returns_empty_when_no_template(): void {
		$provider = new CustomShippingProvider( 'test', 'Test', '', '' );

		$result = $provider->get_tracking_url( 'ABC123' );

		$this->assertSame( '', $result );
	}

	/**
	 * @testdox Should always return null for try_parse_tracking_number.
	 */
	public function test_try_parse_tracking_number_returns_null(): void {
		$result = $this->sut->try_parse_tracking_number( 'ABC123', 'US', 'CA' );

		$this->assertNull( $result );
	}

	/**
	 * @testdox Should return empty arrays for shipping country methods.
	 */
	public function test_shipping_countries_return_empty_arrays(): void {
		$this->assertSame( array(), $this->sut->get_shipping_from_countries() );
		$this->assertSame( array(), $this->sut->get_shipping_to_countries() );
	}
}
