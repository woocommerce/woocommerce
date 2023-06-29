/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

const isConversionPossible = () => {
	return false;
};

const getDescription = () => {
	return __(
		'This block represents the classic template used to display the order confirmation. The actual rendered template may appear different from this placeholder.',
		'woo-gutenberg-products-block'
	);
};

const getSkeleton = () => {
	return (
		<div className="woocommerce-page">
			<div className="woocommerce-order">
				<h1>
					{ __( 'Order received', 'woo-gutenberg-products-block' ) }
				</h1>
				<p className="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-confirmation">
					{ __(
						'Thank you. Your order has been received.',
						'woo-gutenberg-products-block'
					) }
				</p>
				<ul className="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
					<li className="woocommerce-order-overview__order order">
						{ __( 'Order number', 'woo-gutenberg-products-block' ) }
						: <strong>123</strong>
					</li>
					<li className="woocommerce-order-overview__date date">
						{ __( 'Date', 'woo-gutenberg-products-block' ) }:{ ' ' }
						<strong>May 25, 2023</strong>
					</li>
					<li className="woocommerce-order-overview__email email">
						{ __( 'Email', 'woo-gutenberg-products-block' ) }:{ ' ' }
						<strong>shopper@woo.com</strong>
					</li>
					<li className="woocommerce-order-overview__total total">
						{ __( 'Total', 'woo-gutenberg-products-block' ) }:{ ' ' }
						<strong>$20.00</strong>
					</li>
				</ul>

				<section className="woocommerce-order-details">
					<h2 className="woocommerce-order-details__title">
						{ __(
							'Order details',
							'woo-gutenberg-products-block'
						) }
					</h2>
					<table className="woocommerce-table woocommerce-table--order-details shop_table order_details">
						<thead>
							<tr>
								<th className="woocommerce-table__product-name product-name">
									{ __(
										'Product',
										'woo-gutenberg-products-block'
									) }
								</th>
								<th className="woocommerce-table__product-table product-total">
									{ __(
										'Total',
										'woo-gutenberg-products-block'
									) }
								</th>
							</tr>
						</thead>
						<tbody>
							<tr className="woocommerce-table__line-item order_item">
								<td className="woocommerce-table__product-name product-name">
									Sample Product{ ' ' }
									<strong className="product-quantity">
										×&nbsp;2
									</strong>{ ' ' }
								</td>

								<td className="woocommerce-table__product-total product-total">
									$20.00
								</td>
							</tr>
						</tbody>
						<tfoot>
							<tr>
								<th scope="row">
									{ __(
										'Subtotal',
										'woo-gutenberg-products-block'
									) }
									:
								</th>
								<td>$20.00</td>
							</tr>
							<tr>
								<th scope="row">
									{ __(
										'Total',
										'woo-gutenberg-products-block'
									) }
									:
								</th>
								<td>$20.00</td>
							</tr>
						</tfoot>
					</table>
				</section>

				<section className="woocommerce-customer-details">
					<section className="woocommerce-columns woocommerce-columns--2 woocommerce-columns--addresses col2-set addresses">
						<div className="woocommerce-column woocommerce-column--1 woocommerce-column--billing-address col-1">
							<h2 className="woocommerce-column__title">
								{ __(
									'Billing address',
									'woo-gutenberg-products-block'
								) }
							</h2>
							<address>
								123 Main St
								<br />
								New York, NY 10001
								<br />
								United States (US)
							</address>
						</div>

						<div className="woocommerce-column woocommerce-column--2 woocommerce-column--shipping-address col-2">
							<h2 className="woocommerce-column__title">
								{ __(
									'Shipping address',
									'woo-gutenberg-products-block'
								) }
							</h2>
							<address>
								123 Main St
								<br />
								New York, NY 10001
								<br />
								United States (US)
							</address>
						</div>
					</section>
				</section>
			</div>
		</div>
	);
};

export { isConversionPossible, getDescription, getSkeleton };
