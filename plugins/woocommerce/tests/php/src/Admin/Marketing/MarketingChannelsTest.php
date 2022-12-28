<?php

namespace Automattic\WooCommerce\Tests\Admin\Marketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannels;
use WC_Unit_Test_Case;

/**
 * Tests for the MarketingChannels class.
 */
class MarketingChannelsTest extends WC_Unit_Test_Case {

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		remove_all_filters( 'woocommerce_marketing_channels' );
	}

	/**
	 * @testdox A marketing channel can be registered using the `register` method if the same channel slug is NOT previously registered.
	 */
	public function test_registers_channel() {
		$test_channel = $this->createMock( MarketingChannelInterface::class );
		$test_channel->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );

		$marketing_channels = new MarketingChannels();
		$marketing_channels->register( $test_channel );

		$this->assertNotEmpty( $marketing_channels->get_registered_channels() );
		$this->assertEquals( $test_channel, $marketing_channels->get_registered_channels()[0] );
	}

	/**
	 * @testdox A marketing channel can NOT be registered using the `register` method if it is previously registered.
	 */
	public function test_throws_exception_if_registering_existing_channels() {
		$test_channel_1 = $this->createMock( MarketingChannelInterface::class );
		$test_channel_1->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );

		$test_channel_1_duplicate = $this->createMock( MarketingChannelInterface::class );
		$test_channel_1_duplicate->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );

		$marketing_channels = new MarketingChannels();
		$marketing_channels->register( $test_channel_1 );

		$this->expectException( \Exception::class );
		$marketing_channels->register( $test_channel_1_duplicate );

		$this->assertCount( 1, $marketing_channels->get_registered_channels() );
		$this->assertEquals( $test_channel_1, $marketing_channels->get_registered_channels()[0] );
	}

	/**
	 * @testdox A marketing channel can be registered using the `woocommerce_marketing_channels` WordPress filter if the same channel slug is NOT previously registered.
	 */
	public function test_registers_channel_using_wp_filter() {
		$test_channel = $this->createMock( MarketingChannelInterface::class );
		$test_channel->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );

		$marketing_channels = new MarketingChannels();

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
	 * @testdox A marketing channel can NOT be registered using the `woocommerce_marketing_channels` WordPress filter if it is previously registered.
	 */
	public function test_overrides_existing_channel_if_registered_using_wp_filter() {
		$marketing_channels = new MarketingChannels();

		$test_channel_1 = $this->createMock( MarketingChannelInterface::class );
		$test_channel_1->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );

		$marketing_channels->register( $test_channel_1 );

		$test_channel_1_duplicate = $this->createMock( MarketingChannelInterface::class );
		$test_channel_1_duplicate->expects( $this->any() )->method( 'get_slug' )->willReturn( 'test-channel-1' );

		add_filter(
			'woocommerce_marketing_channels',
			function ( array $channels ) use ( $test_channel_1_duplicate ) {
				$channels[ $test_channel_1_duplicate->get_slug() ] = $test_channel_1_duplicate;

				return $channels;
			}
		);

		$this->assertCount( 1, $marketing_channels->get_registered_channels() );
		$this->assertEquals( $test_channel_1_duplicate, $marketing_channels->get_registered_channels()[0] );
	}
}
