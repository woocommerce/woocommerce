<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\BlockTypes\OrderConfirmation;

use Automattic\WooCommerce\Blocks\BlockTypes\OrderConfirmation\Status;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Order;
use WC_Unit_Test_Case;
use WP_Block;

/**
 * Tests for the order confirmation Status block.
 */
class StatusTest extends WC_Unit_Test_Case {
	/**
	 * @testdox The block renders the saved state matching the order status.
	 * @dataProvider provider_status_content
	 *
	 * @param string $status Order status.
	 * @param string $state State block name suffix.
	 * @param string $default_title Default status title.
	 * @param string $default_text Default status text.
	 */
	public function test_renders_matching_custom_status_state( string $status, string $state, string $default_title, string $default_text ): void {
		unset( $default_title, $default_text );
		$order = \WC_Helper_Order::create_order();
		$order->set_status( $status );
		$sut     = $this->create_status_block( $order );
		$content = $sut->render_for_test( $this->create_parent_block_with_status_states() );

		$this->assertStringContainsString( ucfirst( $state ) . ' custom heading', $content, 'The matching status heading should render.' );
		$this->assertStringContainsString( ucfirst( $state ) . ' custom paragraph', $content, 'The matching status paragraph should render.' );
		$this->assertStringNotContainsString( 'wp-block-woocommerce-order-confirmation-status-' . $state, $content, 'State wrappers should not change the public frontend hierarchy.' );

		foreach ( array( 'default', 'cancelled', 'refunded', 'completed', 'failed' ) as $other_state ) {
			if ( $state !== $other_state ) {
				$this->assertStringNotContainsString( ucfirst( $other_state ) . ' custom heading', $content, 'Other status states should not render.' );
			}
		}

		$order->delete( true );
	}

	/**
	 * @testdox Existing blocks without inner states retain default status copy.
	 * @dataProvider provider_status_content
	 *
	 * @param string $status Order status.
	 * @param string $state State block name suffix.
	 * @param string $default_title Default status title.
	 * @param string $default_text Default status text.
	 */
	public function test_renders_default_content_without_saved_status_states( string $status, string $state, string $default_title, string $default_text ): void {
		unset( $state );
		$order = \WC_Helper_Order::create_order();
		$order->set_status( $status );
		$sut     = $this->create_status_block( $order );
		$content = $sut->render_for_test( $this->create_parent_block( array() ) );
		if ( OrderStatus::REFUNDED === $status ) {
			$default_text = sprintf( $default_text, wc_format_datetime( $order->get_date_modified() ) );
		}

		$this->assertStringContainsString( '<h1>' . $default_title . '</h1>', $content, 'The default status heading should render.' );
		$this->assertStringContainsString( '<p>' . $default_text . '</p>', $content, 'The default status paragraph should render.' );

		$order->delete( true );
	}

	/**
	 * @testdox Existing thank-you filters receive and can update customized block content.
	 */
	public function test_applies_filters_to_custom_status_content(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$sut = $this->create_status_block( $order );

		$title_filter = static function ( $title, $filtered_order ) use ( $order ) {
			if ( $order !== $filtered_order ) {
				return $title;
			}

			return '<strong>' . $title . ' filtered</strong>';
		};
		$text_filter  = static function ( $text, $filtered_order ) use ( $order ) {
			if ( $order !== $filtered_order ) {
				return $text;
			}

			return '<em>' . $text . ' filtered</em>';
		};

		add_filter( 'woocommerce_thankyou_order_received_title', $title_filter, 10, 2 );
		add_filter( 'woocommerce_thankyou_order_received_text', $text_filter, 10, 2 );
		$content = $sut->render_for_test( $this->create_parent_block_with_status_states() );
		remove_filter( 'woocommerce_thankyou_order_received_title', $title_filter, 10 );
		remove_filter( 'woocommerce_thankyou_order_received_text', $text_filter, 10 );

		$this->assertStringContainsString( '<strong>Completed custom heading filtered</strong>', $content, 'The title filter should receive the customized heading.' );
		$this->assertStringContainsString( '<em>Completed custom paragraph filtered</em>', $content, 'The text filter should receive the customized paragraph.' );

		$order->delete( true );
	}

	/**
	 * @testdox Legacy refunded filters retain their date placeholder behavior.
	 */
	public function test_legacy_refunded_filter_retains_date_placeholder(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::REFUNDED );
		$sut           = $this->create_status_block( $order );
		$received_text = '';
		$text_filter   = static function ( $text, $filtered_order ) use ( $order, &$received_text ) {
			if ( $order === $filtered_order ) {
				$received_text = $text;
				return 'Refunded at %s';
			}

			return $text;
		};

		add_filter( 'woocommerce_thankyou_order_received_text', $text_filter, 10, 2 );
		$content = $sut->render_for_test( $this->create_parent_block( array() ) );
		remove_filter( 'woocommerce_thankyou_order_received_text', $text_filter, 10 );

		$this->assertSame( 'Your order was refunded %s.', $received_text, 'The legacy filter should receive its original format string.' );
		$this->assertStringContainsString( 'Refunded at ' . wc_format_datetime( $order->get_date_modified() ), $content, 'The filtered date placeholder should be substituted.' );

		$order->delete( true );
	}

	/**
	 * @testdox Legacy failed-order filters retain their historical HTML support.
	 */
	public function test_legacy_failed_filter_retains_html_support(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::FAILED );
		$sut         = $this->create_status_block( $order );
		$text_filter = static function ( $text, $filtered_order ) {
			if ( null !== $filtered_order ) {
				return $text;
			}

			return '<svg data-filtered="true"><title>Failed</title></svg>';
		};

		add_filter( 'woocommerce_thankyou_order_received_text', $text_filter, 10, 2 );
		$content = $sut->render_for_test( $this->create_parent_block( array() ) );
		remove_filter( 'woocommerce_thankyou_order_received_text', $text_filter, 10 );

		$this->assertStringContainsString( '<svg data-filtered="true"><title>Failed</title></svg>', $content, 'Legacy failed-order filter HTML should remain unchanged.' );

		$order->delete( true );
	}

	/**
	 * @testdox The selected state runs standard block rendering filters once.
	 */
	public function test_selected_state_runs_block_render_filter_once(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$sut          = $this->create_status_block( $order );
		$filter_calls = 0;
		$block_filter = static function ( $content ) use ( &$filter_calls ) {
			++$filter_calls;
			return '<section data-filtered="true">' . $content . '</section>';
		};

		add_filter( 'render_block_woocommerce/order-confirmation-status-completed', $block_filter );
		$content = $sut->render_for_test( $this->create_parent_block_with_status_states() );
		remove_filter( 'render_block_woocommerce/order-confirmation-status-completed', $block_filter );

		$this->assertSame( 1, $filter_calls, 'The selected state should run its block rendering filter exactly once.' );
		$this->assertStringContainsString( '<section data-filtered="true"><h2', $content, 'State block filter output should remain unchanged.' );

		$order->delete( true );
	}

	/**
	 * @testdox Legacy failed-order hooks retain their historical order.
	 */
	public function test_legacy_failed_hooks_retain_order(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::FAILED );
		$sut      = $this->create_status_block( $order );
		$sequence = array();

		$text_filter  = static function ( $text ) use ( &$sequence ) {
			$sequence[] = 'text';
			return $text;
		};
		$url_filter   = static function ( $url ) use ( &$sequence ) {
			$sequence[] = 'payment-url';
			return $url;
		};
		$title_filter = static function ( $title ) use ( &$sequence ) {
			$sequence[] = 'title';
			return $title;
		};

		add_filter( 'woocommerce_thankyou_order_received_text', $text_filter );
		add_filter( 'woocommerce_get_checkout_payment_url', $url_filter );
		add_filter( 'woocommerce_thankyou_order_received_title', $title_filter );
		$sut->render_for_test( $this->create_parent_block( array() ) );
		remove_filter( 'woocommerce_thankyou_order_received_text', $text_filter );
		remove_filter( 'woocommerce_get_checkout_payment_url', $url_filter );
		remove_filter( 'woocommerce_thankyou_order_received_title', $title_filter );

		$this->assertSame( array( 'text', 'payment-url', 'title' ), $sequence, 'Legacy failed-order hooks should run in their historical order.' );

		$order->delete( true );
	}

	/**
	 * Status content data provider.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_status_content(): array {
		return array(
			'processing uses default' => array(
				OrderStatus::PROCESSING,
				'default',
				'Order received',
				'Thank you. Your order has been received.',
			),
			'on hold uses default'    => array(
				OrderStatus::ON_HOLD,
				'default',
				'Order received',
				'Thank you. Your order has been received.',
			),
			'cancelled'               => array(
				OrderStatus::CANCELLED,
				'cancelled',
				'Order cancelled',
				'Your order has been cancelled.',
			),
			'refunded'                => array(
				OrderStatus::REFUNDED,
				'refunded',
				'Order refunded',
				'Your order was refunded %s.',
			),
			'completed'               => array(
				OrderStatus::COMPLETED,
				'completed',
				'Order completed',
				'Thank you. Your order has been fulfilled.',
			),
			'failed'                  => array(
				OrderStatus::FAILED,
				'failed',
				'Order failed',
				'Your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.',
			),
		);
	}

	/**
	 * Create a testable Status block instance.
	 *
	 * @param WC_Order $order Order object.
	 * @return object
	 */
	private function create_status_block( WC_Order $order ) {
		return new class( $order ) extends Status {
			/**
			 * Test order.
			 *
			 * @var WC_Order
			 */
			private $test_order;

			/**
			 * Constructor.
			 *
			 * @param WC_Order $order Order object.
			 */
			public function __construct( WC_Order $order ) {
				$this->test_order = $order;
			}

			/**
			 * Render the block for a test.
			 *
			 * @param WP_Block $block Block instance.
			 * @return string
			 */
			public function render_for_test( WP_Block $block ): string {
				return $this->render( array(), '', $block );
			}

			/**
			 * Get the test order.
			 *
			 * @return WC_Order
			 */
			protected function get_order() {
				return $this->test_order;
			}

			/**
			 * Allow the test order to render.
			 *
			 * @param WC_Order $order Order object.
			 * @return string
			 */
			protected function get_view_order_permissions( $order ) {
				unset( $order );

				return 'full';
			}
		};
	}

	/**
	 * Create a parent block containing each customizable status state.
	 *
	 * @return WP_Block
	 */
	private function create_parent_block_with_status_states(): WP_Block {
		$states = array( 'default', 'cancelled', 'refunded', 'completed', 'failed' );
		$blocks = array_map(
			function ( $state ) {
				return $this->create_status_state_block( $state );
			},
			$states
		);

		return $this->create_parent_block( $blocks );
	}

	/**
	 * Create a parent WP_Block.
	 *
	 * @param array $inner_blocks Parsed inner blocks.
	 * @return WP_Block
	 */
	private function create_parent_block( array $inner_blocks ): WP_Block {
		return new WP_Block(
			array(
				'blockName'    => 'woocommerce/order-confirmation-status',
				'attrs'        => array(),
				'innerBlocks'  => $inner_blocks,
				'innerHTML'    => '',
				'innerContent' => array_fill( 0, count( $inner_blocks ), null ),
			)
		);
	}

	/**
	 * Create a parsed order status state block.
	 *
	 * @param string $state State block name suffix.
	 * @return array
	 */
	private function create_status_state_block( string $state ): array {
		$label          = ucfirst( $state );
		$heading        = $label . ' custom heading';
		$paragraph      = $label . ' custom paragraph';
		$heading_html   = '<h2 class="wp-block-heading custom-heading">' . $heading . '</h2>';
		$paragraph_html = '<p class="custom-paragraph">' . $paragraph . '</p>';
		$opening_html   = '<div class="wp-block-woocommerce-order-confirmation-status-' . $state . '">';

		return array(
			'blockName'    => 'woocommerce/order-confirmation-status-' . $state,
			'attrs'        => array(),
			'innerBlocks'  => array(
				array(
					'blockName'    => 'core/heading',
					'attrs'        => array( 'level' => 2 ),
					'innerBlocks'  => array(),
					'innerHTML'    => $heading_html,
					'innerContent' => array( $heading_html ),
				),
				array(
					'blockName'    => 'core/paragraph',
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => $paragraph_html,
					'innerContent' => array( $paragraph_html ),
				),
			),
			'innerHTML'    => $opening_html . '</div>',
			'innerContent' => array( $opening_html, null, null, '</div>' ),
		);
	}
}
