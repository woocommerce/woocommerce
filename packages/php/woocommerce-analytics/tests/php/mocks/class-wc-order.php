<?php
/**
 * Mock WC_Order class for testing.
 *
 * @package automattic/woocommerce-analytics
 */

if ( ! class_exists( 'WC_Order' ) ) {
	/**
	 * Mock WC_Order class for testing.
	 */
	class WC_Order {
		/**
		 * Order ID.
		 *
		 * @var int
		 */
		private $id = 123;

		/**
		 * Order items.
		 *
		 * @var array
		 */
		private $items = array();

		/**
		 * Order coupons.
		 *
		 * @var array
		 */
		private $coupons = array();

		/**
		 * Get order ID.
		 *
		 * @return int
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Get payment method.
		 *
		 * @return string
		 */
		public function get_payment_method() {
			return 'stripe';
		}

		/**
		 * Get payment method title.
		 *
		 * @return string
		 */
		public function get_payment_method_title() {
			return 'Credit Card';
		}

		/**
		 * Get user.
		 *
		 * @return object|false
		 */
		public function get_user() {
			return false;
		}

		/**
		 * Get created via.
		 *
		 * @return string
		 */
		public function get_created_via() {
			return 'checkout';
		}

		/**
		 * Get items.
		 *
		 * @return array
		 */
		public function get_items() {
			return $this->items;
		}

		/**
		 * Set items for testing.
		 *
		 * @param array $items Items to set.
		 */
		public function set_items( $items ) {
			$this->items = $items;
		}

		/**
		 * Get coupons.
		 *
		 * @return array
		 */
		public function get_coupons() {
			return $this->coupons;
		}

		/**
		 * Get order number.
		 *
		 * @return string
		 */
		public function get_order_number() {
			return '123';
		}

		/**
		 * Get subtotal.
		 *
		 * @return float
		 */
		public function get_subtotal() {
			return 100.00;
		}

		/**
		 * Get total.
		 *
		 * @return float
		 */
		public function get_total() {
			return 110.00;
		}

		/**
		 * Get discount total.
		 *
		 * @return float
		 */
		public function get_discount_total() {
			return 0.00;
		}

		/**
		 * Get total tax.
		 *
		 * @return float
		 */
		public function get_total_tax() {
			return 10.00;
		}

		/**
		 * Get shipping total.
		 *
		 * @return float
		 */
		public function get_shipping_total() {
			return 5.00;
		}
	}
}
