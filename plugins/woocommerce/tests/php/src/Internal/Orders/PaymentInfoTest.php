<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Unit_Test_Case;

/**
 * Class PaymentInfoTest.
 */
class PaymentInfoTest extends WC_Unit_Test_Case {
	/**
	 *
	 */
	const ENCODED_VISA_CARD_ICON = 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+Cjxzdmcgdmlld0JveD0iMCAwIDc1MCA0NzEiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sbnM6c2tldGNoPSJodHRwOi8vd3d3LmJvaGVtaWFuY29kaW5nLmNvbS9za2V0Y2gvbnMiIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaWRZTWlkIG1lZXQiPgogICAgPCEtLSBHZW5lcmF0b3I6IFNrZXRjaCAzLjMuMSAoMTIwMDUpIC0gaHR0cDovL3d3dy5ib2hlbWlhbmNvZGluZy5jb20vc2tldGNoIC0tPgogICAgPHRpdGxlPlNsaWNlIDE8L3RpdGxlPgogICAgPGRlc2M+Q3JlYXRlZCB3aXRoIFNrZXRjaC48L2Rlc2M+CiAgICA8ZGVmcz48L2RlZnM+CiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIiBza2V0Y2g6dHlwZT0iTVNQYWdlIj4KICAgICAgICA8ZyBpZD0idmlzYSIgc2tldGNoOnR5cGU9Ik1TTGF5ZXJHcm91cCI+CiAgICAgICAgICAgIDxyZWN0IGlkPSJSZWN0YW5nbGUtMSIgZmlsbD0iIzBFNDU5NSIgc2tldGNoOnR5cGU9Ik1TU2hhcGVHcm91cCIgeD0iMCIgeT0iMCIgd2lkdGg9Ijc1MCIgaGVpZ2h0PSI0NzEiIHJ4PSI0MCI+PC9yZWN0PgogICAgICAgICAgICA8cGF0aCBkPSJNMjc4LjE5NzUsMzM0LjIyNzUgTDMxMS41NTg1LDEzOC40NjU1IEwzNjQuOTE3NSwxMzguNDY1NSBMMzMxLjUzMzUsMzM0LjIyNzUgTDI3OC4xOTc1LDMzNC4yMjc1IEwyNzguMTk3NSwzMzQuMjI3NSBaIiBpZD0iU2hhcGUiIGZpbGw9IiNGRkZGRkYiIHNrZXRjaDp0eXBlPSJNU1NoYXBlR3JvdXAiPjwvcGF0aD4KICAgICAgICAgICAgPHBhdGggZD0iTTUyNC4zMDc1LDE0Mi42ODc1IEM1MTMuNzM1NSwxMzguNzIxNSA0OTcuMTcxNSwxMzQuNDY1NSA0NzYuNDg0NSwxMzQuNDY1NSBDNDIzLjc2MDUsMTM0LjQ2NTUgMzg2LjYyMDUsMTYxLjAxNjUgMzg2LjMwNDUsMTk5LjA2OTUgQzM4Ni4wMDc1LDIyNy4xOTg1IDQxMi44MTg1LDI0Mi44OTA1IDQzMy4wNTg1LDI1Mi4yNTQ1IEM0NTMuODI3NSwyNjEuODQ5NSA0NjAuODEwNSwyNjcuOTY5NSA0NjAuNzExNSwyNzYuNTM3NSBDNDYwLjU3OTUsMjg5LjY1OTUgNDQ0LjEyNTUsMjk1LjY1NDUgNDI4Ljc4ODUsMjk1LjY1NDUgQzQwNy40MzE1LDI5NS42NTQ1IDM5Ni4wODU1LDI5Mi42ODc1IDM3OC41NjI1LDI4NS4zNzg1IEwzNzEuNjg2NSwyODIuMjY2NSBMMzY0LjE5NzUsMzI2LjA5MDUgQzM3Ni42NjA1LDMzMS41NTQ1IDM5OS43MDY1LDMzNi4yODk1IDQyMy42MzU1LDMzNi41MzQ1IEM0NzkuNzI0NSwzMzYuNTM0NSA1MTYuMTM2NSwzMTAuMjg3NSA1MTYuNTUwNSwyNjkuNjUyNSBDNTE2Ljc1MTUsMjQ3LjM4MzUgNTAyLjUzNTUsMjMwLjQzNTUgNDcxLjc1MTUsMjE2LjQ2NDUgQzQ1My4xMDA1LDIwNy40MDg1IDQ0MS42Nzg1LDIwMS4zNjU1IDQ0MS43OTk1LDE5Mi4xOTU1IEM0NDEuNzk5NSwxODQuMDU4NSA0NTEuNDY3NSwxNzUuMzU3NSA0NzIuMzU2NSwxNzUuMzU3NSBDNDg5LjgwNTUsMTc1LjA4NjUgNTAyLjQ0NDUsMTc4Ljg5MTUgNTEyLjI5MjUsMTgyLjg1NzUgTDUxNy4wNzQ1LDE4NS4xMTY1IEw1MjQuMzA3NSwxNDIuNjg3NSIgaWQ9InBhdGgxMyIgZmlsbD0iI0ZGRkZGRiIgc2tldGNoOnR5cGU9Ik1TU2hhcGVHcm91cCI+PC9wYXRoPgogICAgICAgICAgICA8cGF0aCBkPSJNNjYxLjYxNDUsMTM4LjQ2NTUgTDYyMC4zODM1LDEzOC40NjU1IEM2MDcuNjEwNSwxMzguNDY1NSA1OTguMDUyNSwxNDEuOTUxNSA1OTIuNDQyNSwxNTQuNjk5NSBMNTEzLjE5NzUsMzM0LjEwMjUgTDU2OS4yMjg1LDMzNC4xMDI1IEM1NjkuMjI4NSwzMzQuMTAyNSA1NzguMzkwNSwzMDkuOTgwNSA1ODAuNDYyNSwzMDQuNjg0NSBDNTg2LjU4NTUsMzA0LjY4NDUgNjQxLjAxNjUsMzA0Ljc2ODUgNjQ4Ljc5ODUsMzA0Ljc2ODUgQzY1MC4zOTQ1LDMxMS42MjE1IDY1NS4yOTA1LDMzNC4xMDI1IDY1NS4yOTA1LDMzNC4xMDI1IEw3MDQuODAyNSwzMzQuMTAyNSBMNjYxLjYxNDUsMTM4LjQ2NTUgTDY2MS42MTQ1LDEzOC40NjU1IFogTTU5Ni4xOTc1LDI2NC44NzI1IEM2MDAuNjEwNSwyNTMuNTkzNSA2MTcuNDU2NSwyMTAuMTQ5NSA2MTcuNDU2NSwyMTAuMTQ5NSBDNjE3LjE0MTUsMjEwLjY3MDUgNjIxLjgzNjUsMTk4LjgxNTUgNjI0LjUzMTUsMTkxLjQ2NTUgTDYyOC4xMzg1LDIwOC4zNDM1IEM2MjguMTM4NSwyMDguMzQzNSA2MzguMzU1NSwyNTUuMDcyNSA2NDAuNDkwNSwyNjQuODcxNSBMNTk2LjE5NzUsMjY0Ljg3MTUgTDU5Ni4xOTc1LDI2NC44NzI1IEw1OTYuMTk3NSwyNjQuODcyNSBaIiBpZD0iUGF0aCIgZmlsbD0iI0ZGRkZGRiIgc2tldGNoOnR5cGU9Ik1TU2hhcGVHcm91cCI+PC9wYXRoPgogICAgICAgICAgICA8cGF0aCBkPSJNMjMyLjkwMjUsMTM4LjQ2NTUgTDE4MC42NjI1LDI3MS45NjA1IEwxNzUuMDk2NSwyNDQuODMxNSBDMTY1LjM3MTUsMjEzLjU1NzUgMTM1LjA3MTUsMTc5LjY3NTUgMTAxLjE5NzUsMTYyLjcxMjUgTDE0OC45NjQ1LDMzMy45MTU1IEwyMDUuNDE5NSwzMzMuODUwNSBMMjg5LjQyMzUsMTM4LjQ2NTUgTDIzMi45MDI1LDEzOC40NjU1IiBpZD0icGF0aDE2IiBmaWxsPSIjRkZGRkZGIiBza2V0Y2g6dHlwZT0iTVNTaGFwZUdyb3VwIj48L3BhdGg+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik0xMzEuOTE5NSwxMzguNDY1NSBMNDUuODc4NSwxMzguNDY1NSBMNDUuMTk3NSwxNDIuNTM4NSBDMTEyLjEzNjUsMTU4Ljc0MjUgMTU2LjQyOTUsMTk3LjkwMTUgMTc0LjgxNTUsMjQ0Ljk1MjUgTDE1Ni4xMDY1LDE1NC45OTI1IEMxNTIuODc2NSwxNDIuNTk2NSAxNDMuNTA4NSwxMzguODk3NSAxMzEuOTE5NSwxMzguNDY1NSIgaWQ9InBhdGgxOCIgZmlsbD0iI0YyQUUxNCIgc2tldGNoOnR5cGU9Ik1TU2hhcGVHcm91cCI+PC9wYXRoPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+';

	/**
	 * @testdox The `get_card_info` method should return an associative array with specific keys when an order has
	 *          payment card info available.
	 */
	public function test_get_card_info_wcpay_online(): void {
		$order = OrderHelper::create_order();

		$order->set_payment_method( 'woocommerce_payments' );
		$order->add_meta_data(
			'_wcpay_raw_payment_method_details',
			'{"card":{"amount_authorized":4500,"authorization_code":null,"brand":"visa","checks":{"address_line1_check":"pass","address_postal_code_check":"pass","cvc_check":"pass"},"country":"US","description":"Visa Classic","exp_month":12,"exp_year":2034,"extended_authorization":{"status":"disabled"},"fingerprint":"redacted","funding":"credit","iin":"424242","incremental_authorization":{"status":"unavailable"},"installments":null,"issuer":"Unit Test","last4":"4242","mandate":null,"multicapture":{"status":"unavailable"},"network":"visa","network_token":{"used":false},"overcapture":{"maximum_amount_capturable":4500,"status":"unavailable"},"three_d_secure":null,"wallet":null},"type":"card"}',
			true
		);
		$order->save();

		$result = $order->get_payment_card_info();

		$this->assertArrayHasKey( 'payment_method', $result );
		$this->assertEquals( 'woocommerce_payments', $result['payment_method'] );
		$this->assertArrayHasKey( 'brand', $result );
		$this->assertEquals( 'visa', $result['brand'] );
		$this->assertArrayHasKey( 'icon', $result );
		$this->assertEquals( self::ENCODED_VISA_CARD_ICON, $result['icon'] );
		$this->assertArrayHasKey( 'last4', $result );
		$this->assertEquals( '4242', $result['last4'] );
	}

	/**
	 * @testdox The `get_card_info` method should prefer the filter result over the metadata.
	 */
	public function test_get_card_info_prefers_filter() {
		$order = OrderHelper::create_order();

		// Prepare the fallback data in case the filter was not triggered.
		$order->set_payment_method( 'woocommerce_payments' );
		$order->add_meta_data(
			'_wcpay_raw_payment_method_details',
			'{"card":{"amount_authorized":4500,"authorization_code":null,"brand":"visa","checks":{"address_line1_check":"pass","address_postal_code_check":"pass","cvc_check":"pass"},"country":"US","description":"Visa Classic","exp_month":12,"exp_year":2034,"extended_authorization":{"status":"disabled"},"fingerprint":"redacted","funding":"credit","iin":"424242","incremental_authorization":{"status":"unavailable"},"installments":null,"issuer":"Unit Test","last4":"4242","mandate":null,"multicapture":{"status":"unavailable"},"network":"visa","network_token":{"used":false},"overcapture":{"maximum_amount_capturable":4500,"status":"unavailable"},"three_d_secure":null,"wallet":null},"type":"card"}',
			true
		);
		$order->save();

		// Add a dummy callback to the filter. It should be preferred over metadata.
		$filter_callback = fn() => array( 'payment_method' => 'foo' );
		add_filter( 'wc_order_payment_card_info', $filter_callback );

		$result = $order->get_payment_card_info();

		$this->assertArrayHasKey( 'payment_method', $result );
		$this->assertEquals( 'foo', $result['payment_method'] );

		remove_filter( 'wc_order_payment_card_info', $filter_callback );
	}
}
