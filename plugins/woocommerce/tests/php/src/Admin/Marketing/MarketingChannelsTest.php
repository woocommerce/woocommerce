<?php

namespace Automattic\WooCommerce\Tests\Admin\Marketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannels;
use Automattic\WooCommerce\Internal\Admin\Marketing\MarketingSpecs;
use WC_Unit_Test_Case;

/**
 * Tests for the MarketingChannels class.
 */
class MarketingChannelsTest extends WC_Unit_Test_Case {

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		delete_transient( MarketingSpecs::RECOMMENDED_PLUGINS_TRANSIENT );
	}

	/**
	 * @testdox A marketing channel can be registered using the `register` method if it is in the allowed list.
	 */
	public function test_registers_allowed_channels() {
		$test_channel = $this->createMock( MarketingChannelInterface::class );
		$test_channel->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );

		$marketing_specs = $this->createMock( MarketingSpecs::class );
		$marketing_specs->expects( $this->once() )
						->method( 'get_recommended_plugins' )
						->willReturn(
							[
								[
									'product' => 'test-channel-1',
								],
							]
						);

		$marketing_channels = new MarketingChannels();
		$marketing_channels->init( $marketing_specs );
		$marketing_channels->register( $test_channel );

		$this->assertNotEmpty( $marketing_channels->get_registered_channels() );
		$this->assertEquals( $test_channel, $marketing_channels->get_registered_channels()[0] );
	}

	/**
	 * @testdox A marketing channel can NOT be registered using the `register` method if it is NOT in the allowed list.
	 */
	public function test_does_not_register_disallowed_channels() {
		$test_channel = $this->createMock( MarketingChannelInterface::class );
		$test_channel->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );

		$marketing_specs = $this->createMock( MarketingSpecs::class );
		$marketing_specs->expects( $this->once() )->method( 'get_recommended_plugins' )->willReturn( [] );

		$marketing_channels = new MarketingChannels();
		$marketing_channels->init( $marketing_specs );
		$marketing_channels->register( $test_channel );

		$this->assertEmpty( $marketing_channels->get_registered_channels() );
	}

	/**
	 * @testdox A marketing channel can be registered using the `woocommerce_marketing_channels` WordPress filter if it is in the allowed list.
	 */
	public function test_registers_allowed_channels_using_wp_filter() {
		$test_channel = $this->createMock( MarketingChannelInterface::class );
		$test_channel->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );

		$marketing_specs = $this->createMock( MarketingSpecs::class );
		$marketing_specs->expects( $this->once() )
						->method( 'get_recommended_plugins' )
						->willReturn(
							[
								[
									'product' => 'test-channel-1',
								],
							]
						);

		$marketing_channels = new MarketingChannels();
		$marketing_channels->init( $marketing_specs );

		add_filter(
			'woocommerce_marketing_channels',
			function ( array $channels ) use ( $test_channel ) {
				$channels[ $test_channel->get_slug() ] = $test_channel;

				return $channels;
			}
		);

		$this->assertNotEmpty( $marketing_channels->get_registered_channels() );
		$this->assertEquals( $test_channel, $marketing_channels->get_registered_channels()[0] );
	}

	/**
	 * @testdox A marketing channel can NOT be registered using the `woocommerce_marketing_channels` WordPress filter if it NOT is in the allowed list.
	 */
	public function test_does_not_register_disallowed_channels_using_wp_filter() {
		$test_channel = $this->createMock( MarketingChannelInterface::class );
		$test_channel->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );

		set_transient( MarketingSpecs::RECOMMENDED_PLUGINS_TRANSIENT, [] );

		add_filter(
			'woocommerce_marketing_channels',
			function ( array $channels ) use ( $test_channel ) {
				$channels[ $test_channel->get_slug() ] = $test_channel;

				return $channels;
			}
		);

		$marketing_channels = new MarketingChannels();
		$this->assertEmpty( $marketing_channels->get_registered_channels() );
	}
}
