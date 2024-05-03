<?php

namespace Automattic\WooCommerce\Internal\ReceiptRendering;

use Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\ArrayUtil;
use Automattic\WooCommerce\Utilities\StringUtil;
use Exception;
use WC_Order;

/**
 * This class generates printable order receipts as transient files (see src/Internal/TransientFiles).
 * The template for the receipt is Templates/order-receipt.php, it uses the variables returned as array keys
 * 'get_order_data'.
 *
 * When a receipt is generated for an order with 'generate_receipt' the receipt file name is stored as order meta
 * (see RECEIPT_FILE_NAME_META_KEY) for later retrieval with 'get_existing_receipt'. Beware! The files pointed
 * by such meta keys could have expired and thus no longer exist. 'get_existing_receipt' will appropriately return null
 * if the meta entry exists but the file doesn't.
 */
class ReceiptRenderingEngine {

	private const FONT_SIZE = 12;

	private const LINE_HEIGHT = self::FONT_SIZE * 1.5;

	private const ICON_HEIGHT = self::LINE_HEIGHT;

	private const ICON_WIDTH = self::ICON_HEIGHT * ( 4 / 3 );

	private const MARGIN = 16;

	private const TITLE_FONT_SIZE = 24;

	private const FOOTER_FONT_SIZE = 10;

	/**
	 * This array must contain all the names of the files in the CardIcons directory (without extension),
	 * except 'unknown'.
	 */
	private const KNOWN_CARD_TYPES = array( 'amex', 'diners', 'discover', 'interac', 'jcb', 'mastercard', 'visa' );

	/**
	 * Order meta key that stores the file name of the last generated receipt.
	 */
	public const RECEIPT_FILE_NAME_META_KEY = '_receipt_file_name';

	/**
	 * The instance of TransientFilesEngine to use.
	 *
	 * @var TransientFilesEngine
	 */
	private $transient_files_engine;

	/**
	 * The instance of LegacyProxy to use.
	 *
	 * @var LegacyProxy
	 */
	private $legacy_proxy;

	/**
	 * Initializes the class.
	 *
	 * @param TransientFilesEngine $transient_files_engine The instance of TransientFilesEngine to use.
	 * @param LegacyProxy          $legacy_proxy The instance of LegacyProxy to use.
	 * @internal
	 */
	final public function init( TransientFilesEngine $transient_files_engine, LegacyProxy $legacy_proxy ) {
		$this->transient_files_engine = $transient_files_engine;
		$this->legacy_proxy           = $legacy_proxy;
	}

	/**
	 * Get the (transient) file name of the receipt for an order, creating a new file if necessary.
	 *
	 * If $force_new is false, and a receipt file for the order already exists (as pointed by order meta key
	 * RECEIPT_FILE_NAME_META_KEY), then the name of the already existing receipt file is returned.
	 *
	 * If $force_new is true, OR if it's false but no receipt file for the order exists (no order meta with key
	 * RECEIPT_FILE_NAME_META_KEY exists, OR it exists but the file it points to doesn't), then a new receipt
	 * transient file is created with the supplied expiration date (defaulting to "tomorrow"), and the new file name
	 * is stored as order meta with the key RECEIPT_FILE_NAME_META_KEY.
	 *
	 * @param int|WC_Order    $order The order object or order id to get the receipt for.
	 * @param string|int|null $expiration_date GMT expiration date formatted as yyyy-mm-dd, or as a timestamp, or null for "tomorrow".
	 * @param bool            $force_new If true, creates a new receipt file even if one already exists for the order.
	 * @return string|null The file name of the new or already existing receipt file, null if an order id is passed and the order doesn't exist.
	 * @throws InvalidArgumentException Invalid expiration date (wrongly formatted, or it's a date in the past).
	 * @throws Exception The directory to store the file doesn't exist and can't be created.
	 */
	public function generate_receipt( $order, $expiration_date = null, bool $force_new = false ): ?string {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order );
			if ( false === $order ) {
				return null;
			}
		}

		if ( ! $force_new ) {
			$existing_receipt_filename = $this->get_existing_receipt( $order );
			if ( ! is_null( $existing_receipt_filename ) ) {
				return $existing_receipt_filename;
			}
		}

		$expiration_date ??=
			$this->legacy_proxy->call_function(
				'gmdate',
				'Y-m-d',
				$this->legacy_proxy->call_function(
					'strtotime',
					'+1 days'
				)
			);

		/**
		 * Filter to customize the set of data that is used to render the receipt.
		 * The formatted line items aren't included, use the woocommerce_printable_order_receipt_formatted_line_item
		 * filter to customize those.
		 *
		 * See the value returned by the 'get_order_data' and 'get_woo_pay_data' methods for a reference of
		 * the structure of the data.
		 *
		 * See the template file, Templates/order-receipt.php, for reference on how the data is used.
		 *
		 * @param array $data The original set of data.
		 * @param WC_Order $order The order for which the receipt is being generated.
		 * @returns array The updated set of data.
		 *
		 * @since 9.0.0
		 */
		$data = apply_filters( 'woocommerce_printable_order_receipt_data', $this->get_order_data( $order ), $order );

		$formatted_line_items = array();
		$row_index            = 0;
		foreach ( $data['line_items'] as $line_item_data ) {
			$quantity_data          = isset( $line_item_data['quantity'] ) ? " × ${line_item_data['quantity']}" : '';
			$line_item_display_data = array(
				'inner_html'    => "<td>${line_item_data['title']}$quantity_data</td><td>${line_item_data['amount']}</td>",
				'tr_attributes' => array(),
				'row_index'     => $row_index++,
			);

			/**
			 * Filter to customize the HTML that gets rendered for each order line item in the receipt.
			 *
			 * $line_item_display_data will be passed (and must be returned) with the following keys:
			 *
			 * - inner_html: the HTML text that will go inside a <tr> element, note that
			 *               wp_kses_post will be applied to this text before actual rendering.
			 * - tr_attributes: attributes (e.g. 'class', 'data', 'style') that will be applied to the <tr> element,
			 *                  as an associative array of attribute name => value.
			 * - row_index: a number that starts at 0 and increases by one for each processed line item.
			 *
			 * $line_item_data will contain the following keys:
			 *
			 * - type: One of 'product', 'subtotal', 'discount', 'fee', 'shipping_total', 'taxes_total', 'amount_paid'
			 * - title
			 * - amount (formatted with wc_price)
			 * - item (only when type is 'product'), and instance of WC_Order_Item
			 * - quantity (only when type is 'product')
			 *
			 * @param string $line_item_display_data Data to use to generate the HTML table row to be rendered for the line item.
			 * @param array $line_item_data The relevant data for the line item for which the HTML table row is being generated.
			 * @param WC_Order $order The order for which the receipt is being generated.
			 * @return string The actual data to use to generate the HTML for the line item.
			 *
			 * @since 9.0.0
			 */
			$line_item_display_data = apply_filters( 'woocommerce_printable_order_receipt_line_item_display_data', $line_item_display_data, $line_item_data, $order );
			$attributes             = '';
			foreach ( $line_item_display_data['tr_attributes'] as $attribute_name => $attribute_value ) {
				$attribute_value = esc_attr( $attribute_value );
				$attributes     .= " $attribute_name=\"$attribute_value\"";
			}
			$formatted_line_items[] = wp_kses_post( "<tr$attributes>${line_item_display_data['inner_html']}</tr>" );
		}
		$data['formatted_line_items'] = $formatted_line_items;

		ob_start();
		$css = include __DIR__ . '/Templates/order-receipt-css.php';
		$css = ob_get_contents();
		ob_end_clean();

		/**
		 * Filter to customize the CSS styles used to render the receipt.
		 *
		 * See Templates/order-receipt.php for guidance on the existing HTMl elements and their ids.
		 * See Templates/order-receipt-css.php for the original CSS styles.
		 *
		 * @param string $css The original CSS styles to use.
		 * @param WC_Order $order The order for which the receipt is being generated.
		 * @return string The actual CSS styles that will be used.
		 *
		 * @since 9.0.0
		 */
		$data['css'] = apply_filters( 'woocommerce_printable_order_receipt_css', $css, $order );

		ob_start();
		include __DIR__ . '/Templates/order-receipt.php';
		$rendered_template = ob_get_contents();
		ob_end_clean();

		$file_name = $this->transient_files_engine->create_transient_file( $rendered_template, $expiration_date );

		$order->update_meta_data( self::RECEIPT_FILE_NAME_META_KEY, $file_name );
		$order->save_meta_data();

		return $file_name;
	}

	/**
	 * Get the file name of an existing receipt file for an order.
	 *
	 * A receipt is considered to be available for the order if there's an order meta entry with key
	 * RECEIPT_FILE_NAME_META_KEY AND the transient file it points to exists AND it has not expired.
	 *
	 * @param WC_Order $order The order object or order id to get the receipt for.
	 * @return string|null The receipt file name, or null if no receipt is currently available for the order.
	 * @throws Exception Thrown if a wrong file path is passed.
	 */
	public function get_existing_receipt( $order ): ?string {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order );
			if ( false === $order ) {
				return null;
			}
		}

		$existing_receipt_filename = $order->get_meta( self::RECEIPT_FILE_NAME_META_KEY, true );

		if ( '' === $existing_receipt_filename ) {
			return null;
		}

		$file_path = $this->transient_files_engine->get_transient_file_path( $existing_receipt_filename );
		if ( is_null( $file_path ) ) {
			return null;
		}

		return $this->transient_files_engine->file_has_expired( $file_path ) ? null : $existing_receipt_filename;
	}

	/**
	 * Get the order data that the receipt template will use.
	 *
	 * @param WC_Order $order The order to get the data from.
	 * @return array The order data as an associative array.
	 */
	private function get_order_data( WC_Order $order ): array {
		$store_name = get_bloginfo( 'name' );
		if ( $store_name ) {
			/* translators: %s = store name */
			$receipt_title = sprintf( __( 'Receipt from %s', 'woocommerce' ), $store_name );
		} else {
			$receipt_title = __( 'Receipt', 'woocommerce' );
		}

		$order_id = $order->get_id();
		if ( $order_id ) {
			/* translators: %d = order id */
			$summary_title = sprintf( __( 'Summary: Order #%d', 'woocommerce' ), $order->get_id() );
		} else {
			$summary_title = __( 'Summary', 'woocommerce' );
		}

		$get_price_args = array( 'currency' => $order->get_currency() );

		$line_items_info = array();
		$line_items      = $order->get_items( 'line_item' );
		foreach ( $line_items as $line_item ) {
			$line_item_product = $line_item->get_product();
			if ( false === $line_item_product ) {
				$line_item_title = $line_item->get_name();
			} else {
				$line_item_title =
					( $line_item_product instanceof \WC_Product_Variation ) ?
						( wc_get_product( $line_item_product->get_parent_id() )->get_name() ) . '. ' . $line_item_product->get_attribute_summary() :
						$line_item_product->get_name();
			}
			$line_items_info[] = array(
				'type'     => 'product',
				'item'     => $line_item,
				'title'    => wp_kses( $line_item_title, array() ),
				'quantity' => $line_item->get_quantity(),
				'amount'   => wc_price( $line_item->get_subtotal(), $get_price_args ),
			);
		}

		$line_items_info[] = array(
			'type'   => 'subtotal',
			'title'  => __( 'Subtotal', 'woocommerce' ),
			'amount' => wc_price( $order->get_subtotal(), $get_price_args ),
		);

		$coupon_names = ArrayUtil::select( $order->get_coupons(), 'get_name', ArrayUtil::SELECT_BY_OBJECT_METHOD );
		if ( ! empty( $coupon_names ) ) {
			$line_items_info[] = array(
				'type'   => 'discount',
				/* translators: %s = comma-separated list of coupon codes */
				'title'  => sprintf( __( 'Discount (%s)', 'woocommerce' ), join( ', ', $coupon_names ) ),
				'amount' => wc_price( -$order->get_total_discount(), $get_price_args ),
			);
		}

		foreach ( $order->get_fees() as $fee ) {
			$name              = $fee->get_name();
			$line_items_info[] = array(
				'type'   => 'fee',
				'title'  => '' === $name ? __( 'Fee', 'woocommerce' ) : $name,
				'amount' => wc_price( $fee->get_total(), $get_price_args ),
			);
		}

		$shipping_total = (float) $order->get_shipping_total();
		if ( $shipping_total ) {
			$line_items_info[] = array(
				'type'   => 'shipping_total',
				'title'  => __( 'Shipping', 'woocommerce' ),
				'amount' => wc_price( $order->get_shipping_total(), $get_price_args ),
			);
		}

		$total_taxes = 0;
		foreach ( $order->get_taxes() as $tax ) {
			$total_taxes += (float) $tax->get_tax_total() + (float) $tax->get_shipping_tax_total();
		}

		if ( $total_taxes ) {
			$line_items_info[] = array(
				'type'   => 'taxes_total',
				'title'  => __( 'Taxes', 'woocommerce' ),
				'amount' => wc_price( $total_taxes, $get_price_args ),
			);
		}

		$line_items_info[] = array(
			'type'   => 'amount_paid',
			'title'  => __( 'Amount Paid', 'woocommerce' ),
			'amount' => wc_price( $order->get_total(), $get_price_args ),
		);

		return array(
			'order'            => $order,
			'constants'        => array(
				'font_size'        => self::FONT_SIZE,
				'margin'           => self::MARGIN,
				'title_font_size'  => self::TITLE_FONT_SIZE,
				'footer_font_size' => self::FOOTER_FONT_SIZE,
				'line_height'      => self::LINE_HEIGHT,
				'icon_height'      => self::ICON_HEIGHT,
				'icon_width'       => self::ICON_WIDTH,
			),
			'texts'            => array(
				'receipt_title'                => $receipt_title,
				'amount_paid_section_title'    => __( 'Amount Paid', 'woocommerce' ),
				'date_paid_section_title'      => __( 'Date Paid', 'woocommerce' ),
				'payment_method_section_title' => __( 'Payment method', 'woocommerce' ),
				'summary_section_title'        => $summary_title,
				'order_notes_section_title'    => __( 'Notes', 'woocommerce' ),
				'app_name'                     => __( 'Application Name', 'woocommerce' ),
				'aid'                          => __( 'AID', 'woocommerce' ),
				'account_type'                 => __( 'Account Type', 'woocommerce' ),
			),
			'formatted_amount' => wc_price( $order->get_total(), $get_price_args ),
			'formatted_date'   => wc_format_datetime( $order->get_date_paid() ),
			'line_items'       => $line_items_info,
			'payment_method'   => $order->get_payment_method_title(),
			'notes'            => array_map( 'get_comment_text', $order->get_customer_order_notes() ),
			'payment_info'     => $this->get_woo_pay_data( $order ),
		);
	}

	/**
	 * Get the order data related to WooCommerce Payments.
	 *
	 * It will return null if any of these is true:
	 *
	 * - Payment method is not 'woocommerce_payments".
	 * - WooCommerce Payments is not installed.
	 * - No intent id is stored for the order.
	 * - Retrieving the payment information from Stripe API (providing the intent id) fails.
	 * - The received data set doesn't contain the expected information.
	 *
	 * @param WC_Order $order The order to get the data from.
	 * @return array|null An array of payment information for the order, or null if not available.
	 */
	private function get_woo_pay_data( WC_Order $order ): ?array {
		// For testing purposes: if WooCommerce Payments development mode is enabled,
		// an order meta item with key '_wcpay_payment_details' will be used if it exists as a replacement
		// for the call to the Stripe API's 'get intent' endpoint.
		// The value must be the JSON encoding of an array simulating the "payment_details" part of the response from the endpoint
		// (at the very least it must contain the "card_present" key).
		$payment_details = json_decode( defined( 'WCPAY_DEV_MODE' ) && WCPAY_DEV_MODE ? $order->get_meta( '_wcpay_payment_details' ) : false, true );

		if ( ! $payment_details ) {
			if ( 'woocommerce_payments' !== $order->get_payment_method() ) {
				return null;
			}

			if ( ! class_exists( \WC_Payments::class ) ) {
				return null;
			}

			$intent_id = $order->get_meta( '_intent_id' );
			if ( ! $intent_id ) {
				return null;
			}

			try {
				$payment_details = \WC_Payments::get_payments_api_client()->get_intent( $intent_id )->get_charge()->get_payment_method_details();
			} catch ( Exception $ex ) {
				$order_id = $order->get_id();
				$message  = $ex->getMessage();
				wc_get_logger()->error( StringUtil::class_name_without_namespace( static::class ) . " - retrieving info for charge {$intent_id} for order {$order_id}: {$message}" );
				return null;
			}
		}

		$card_data = $payment_details['card_present'] ?? null;
		if ( is_null( $card_data ) ) {
			return null;
		}

		$card_brand = $card_data['brand'] ?? '';
		if ( ! in_array( $card_brand, self::KNOWN_CARD_TYPES, true ) ) {
			$card_brand = 'unknown';
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$card_svg = base64_encode( file_get_contents( __DIR__ . "/CardIcons/{$card_brand}.svg" ) );

		return array(
			'payment_method' => 'woocommerce_payments',
			'card_icon'      => $card_svg,
			'card_last4'     => wp_kses( $card_data['last4'] ?? '', array() ),
			'app_name'       => wp_kses( $card_data['receipt']['application_preferred_name'] ?? null, array() ),
			'aid'            => wp_kses( $card_data['receipt']['dedicated_file_name'] ?? null, array() ),
			'account_type'   => wp_kses( $card_data['receipt']['account_type'] ?? null, array() ),
		);
	}
}
