<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Emails;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;

/**
 * Email templates controller.
 */
class EmailTemplatesController {

	/**
	 * Initialize the class.
	 *
	 * @internal
	 *
	 * @return void
	 */
	final public function init() {
		add_action( 'init', array( $this, 'register_template_hooks' ) );
	}

	/**
	 * Add template hooks.
	 *
	 * @internal
	 */
	public function register_template_hooks() {
		add_action( 'woocommerce_email_stock_notification_product', array( $this, 'email_product_image' ), 10, 3 );
		add_action( 'woocommerce_email_stock_notification_product', array( $this, 'email_product_title' ), 20, 3 );
		add_action( 'woocommerce_email_stock_notification_product', array( $this, 'email_product_attributes' ), 30, 3 );
		add_action( 'woocommerce_email_stock_notification_product', array( $this, 'email_product_price' ), 40, 3 );
	}

	/**
	 * Email product image.
	 *
	 * @param WC_Product   $product The product object.
	 * @param Notification $notification The notification object.
	 * @param bool         $plain_text Whether the email is plain text.
	 */
	public function email_product_image( $product, $notification, $plain_text = false ) {
		if ( $plain_text ) {
			return;
		}

		$image     = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' );
		$image_src = is_array( $image ) && isset( $image[0] ) ? $image[0] : '';

		ob_start();
		if ( $image_src ) { ?>
				<div id="notification__product__image">
					<img src="<?php echo esc_attr( $image_src ); ?>" alt="<?php echo esc_attr( $product->get_title() ); ?>" width="220"/>
				</div>
			<?php
		}
		$html = ob_get_clean();
		echo wp_kses_post( $html );
	}

	/**
	 * Email product title.
	 *
	 * @param WC_Product   $product The product object.
	 * @param Notification $notification The notification object.
	 * @param bool         $plain_text Whether the email is plain text.
	 */
	public function email_product_title( $product, $notification, $plain_text = false ) {
		if ( $plain_text ) {
			return;
		}

		ob_start();
		?>
		<div id="notification__product__title"><?php echo esc_html( $product->get_name() ); ?></div>
		<?php
		$html = ob_get_clean();
		echo wp_kses_post( $html );
	}

	/**
	 * Email product attributes.
	 *
	 * @param WC_Product   $product The product object.
	 * @param Notification $notification The notification object.
	 * @param bool         $plain_text Whether the email is plain text.
	 */
	public function email_product_attributes( $product, $notification, $plain_text = false ) {
		if ( $plain_text ) {
			return;
		}

		$formatted_variation_list = $notification->get_product_formatted_variation_list( false );
		if ( empty( $formatted_variation_list ) ) {
			return;
		}

		// Convert list to HTML table for better rendering.
		$formatted_variation_list = strtr(
			$formatted_variation_list,
			array(
				'<dl' => '<table',
				'<dd' => '<tr><th',
				'<dt' => '<tr><td',
				'dl>' => 'table>',
				'dd>' => 'th></tr>',
				'dt>' => 'td></tr>',
			)
		);

		ob_start();
		?>
			<div id="notification__product__attributes"><?php echo wp_kses_post( $formatted_variation_list ); ?></div>
		<?php
		$html = ob_get_clean();
		echo wp_kses_post( $html );
	}

	/**
	 * Email product price.
	 *
	 * @param WC_Product   $product The product object.
	 * @param Notification $notification The notification object.
	 * @param bool         $plain_text Whether the email is plain text.
	 */
	public function email_product_price( $product, $notification, $plain_text = false ) {
		if ( $plain_text ) {
			return;
		}

		ob_start();
		?>
		<div id="notification__product__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
		<?php
		$html = ob_get_clean();
		echo wp_kses_post( $html );
	}
}
