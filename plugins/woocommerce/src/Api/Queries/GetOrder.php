<?php

namespace Automattic\WooCommerce\Api\Queries;

use Automattic\WooCommerce\Api\Order;
use Automattic\WooCommerce\Utilities\NumberUtil;

#[RestQueryName('Order')]
class GetOrder extends BaseQuery {

	#[QueryArgument('id', 'The id of the order.')]
	public function run(int $id, array $field_arguments = [], array $fields = null): Order {
		$order = wc_get_order($id);

		$result = new Order();
		$result->id = $order->get_id();
		$result->total_amount = NumberUtil::round($order->get_amount(), $field_arguments['total_amount']['decimals'] ?? 2);
		$result->customer_ip_address = $order->get_customer_ip_address();

		if(is_null($fields) || array_key_exists('fields', 'applied_coupons')) {
			$result->applied_coupons = array_map(
				fn($coupon) => $this->adjust_coupon_data($coupon, $field_arguments['applied_coupons']['_fieldArgs']['amount']['decimals'] ?? 2),
				$order->get_coupons()
			);
		}

		if(is_null($fields) || array_key_exists('fields', 'order_lines')) {
			$order_line_paging_args = $this->get_pagination_arguments($field_arguments, 'order_lines');
			//Get and adjust the order lines for the order based on the pagination arguments, set them in $order->order_lines
		}

		return $result;
	}

	private function adjust_coupon_data(\WC_Order_Item_Coupon $coupon, int $decimals): AppliedCoupon {
		$result = new AppliedCoupon();

		$result->amount = NumberUtil::round($coupon->get_discount(), $decimals);
		$result->type = $coupon->get_type();

		return $result;
	}
}
