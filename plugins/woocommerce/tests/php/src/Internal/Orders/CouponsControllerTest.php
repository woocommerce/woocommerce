<?php

use Automattic\WooCommerce\Internal\Orders\CouponsController;

/**
 * Tests for CouponsController.
 */
class CouponsControllerTest extends \WC_Unit_Test_Case {

	/**
	 * Data provider for test_dehydrate_hydrate_coupon_data.
	 *
	 * @return array[]
	 */
	public function dehydrate_coupon_data_provider() {
		return [
			[
				'percentage50',
				[
					'discount_type' => 'percent',
					'coupon_amount' => 50,
					'product_ids'   => '',
				],
				[],
			],
			[
				'fixedcart10',
				[
					'discount_type'  => 'fixed_cart',
					'coupon_amount'  => 10,
					'usage_limit'    => 20,
					'expiry_date'    => '',
					'minimum_amount' => '25',
				],
				[
					'tag'            => 'great-deal',
				],
			],
		];
	}

	/**
	 * @dataProvider dehydrate_coupon_data_provider
	 * @param string $code Coupon code.
	 * @param array $properties Coupon properties.
	 * @param array $meta Coupon meta.
	 * @return void
	 * @throws WC_Data_Exception
	 */
	public function test_dehydrate_hydrate_coupon_data( string $code, array $properties, array $meta ) {
		$datefmt = 'Y-m-dTH:i:sO';
		$product = WC_Helper_Product::create_simple_product( true, [ 'regular_price' => 100 ] );

		if ( isset( $properties['product_ids'] ) ) {
			$properties['product_ids'] = $product->get_id();
		}
		if ( isset( $properties['expiry_date'] ) ) {
			$expiry                    = new WC_DateTime( '+30 days' );
			$properties['expiry_date'] = $expiry->date( $datefmt );
		}

		$cproperties = array_merge( $properties, $meta );
		$coupon      = WC_Helper_Coupon::create_coupon( $code, $cproperties );
		$coupon->set_description( $properties['description'] ?? '' );
		$coupon->save();
		$coupon_item = new WC_Order_Item_Coupon();
		$coupon_item->set_code( $code );

		$order = WC_Helper_Order::create_order( 0, $product );
		$order->add_item( $coupon_item );
		$order->recalculate_coupons();
		$order->save();
		$order->calculate_totals( true );

		$order1       = wc_get_order( $order->get_id() );
		$data         = $order1->get_data();
		$coupon_lines = $data['coupon_lines'] ?? [];
		$coupon_data  = false;
		foreach ( $coupon_lines as $_meta ) {
			$_data = $_meta->get_data();
			if ( ! empty( $_data['meta_data'] ) ) {
				foreach( $_data['meta_data'] as $meta_row ) {
					if ( $meta_row->key === 'coupon_data' ) {
							$coupon_data = $meta_row->value;
							break 2;
					}
				}
			}
		}
		// Verify the coupon data was found.
		$this->assertNotFalse( $coupon_data );

		// Test the dates.
		foreach( [
					 'date_created'  => 'get_date_created',
					 'date_modified' => 'get_date_modified',
					 'date_expires'  => 'get_date_expires',
				 ] as $key => $callback ) {
			if ( empty( $coupon_data->$key ) ) {
				$this->assertFalse( isset( $properties[ $key ] ) );
			} else {
				$datetime = $coupon->$callback();
				$this->assertSame( $coupon_data->$key->date( $datefmt ), $datetime->date( $datefmt ) );
			}
		}

		// Test falsey fields.
		$default_falsey_fields = wc_get_container()->get( CouponsController::class )->get_default_empty_fields();
		foreach( $default_falsey_fields as $key => $default_value ) {
			if ( $key === 'meta_data' ) {
				continue;
			} elseif ( ! isset( $properties[ $key ] ) ) {
				$this->assertSame( $coupon_data[ $key ], $default_value );
			} else {
				if ( is_array( $default_value ) ) {
					$expected = is_string( $properties[ $key ] ) ?
						explode( ',', $properties[ $key ] ) :
						[ $properties[ $key ] ];
				} else {
					$expected = $properties[ $key ];
				}

				$this->assertSame( $expected, $coupon_data[ $key ] );
			}
		}

		// Test meta data.
		if ( ! empty( $meta ) ) {
			foreach ( $meta as $key => $expected ) {
				$meta_found = false;
				foreach ( $coupon_data['meta_data'] as $meta_row ) {
					if ( $key === $meta_row['key'] ) {
						$meta_found = true;
						$this->assertSame( $expected, $meta_row['value'] );
						break;
					}
				}
				$this->assertTrue( $meta_found );
			}
		}
	}
}
