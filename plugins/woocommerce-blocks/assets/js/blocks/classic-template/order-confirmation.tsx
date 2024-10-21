/**
 * External dependencies
 */
import { createBlock, type BlockInstance } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { OnClickCallbackParameter, InheritedAttributes } from './types';

const isConversionPossible = () => {
	return true;
};

const getButtonLabel = () => __( 'Transform into blocks', 'woocommerce' );

const getBlockifiedTemplate = ( inheritedAttributes: InheritedAttributes ) =>
	[
		createBlock( 'woocommerce/order-confirmation-status', {
			...inheritedAttributes,
			fontSize: 'large',
		} ),
		createBlock(
			'woocommerce/order-confirmation-summary',
			inheritedAttributes
		),
		createBlock(
			'woocommerce/order-confirmation-totals-wrapper',
			inheritedAttributes
		),
		createBlock(
			'woocommerce/order-confirmation-downloads-wrapper',
			inheritedAttributes
		),
		createBlock(
			'core/columns',
			{
				...inheritedAttributes,
				className: 'wc-block-order-confirmation-address-wrapper',
			},
			[
				createBlock( 'core/column', inheritedAttributes, [
					createBlock(
						'woocommerce/order-confirmation-shipping-wrapper',
						inheritedAttributes
					),
				] ),
				createBlock( 'core/column', inheritedAttributes, [
					createBlock(
						'woocommerce/order-confirmation-billing-wrapper',
						inheritedAttributes
					),
				] ),
			]
		),
		createBlock(
			'woocommerce/order-confirmation-additional-fields-wrapper',
			inheritedAttributes
		),
		createBlock(
			'woocommerce/order-confirmation-additional-information',
			inheritedAttributes
		),
	].filter( Boolean ) as BlockInstance[];

const onClickCallback = ( {
	clientId,
	attributes,
	getBlocks,
	replaceBlock,
	selectBlock,
}: OnClickCallbackParameter ) => {
	replaceBlock( clientId, getBlockifiedTemplate( attributes ) );

	const blocks = getBlocks();

	const groupBlock = blocks.find(
		( block ) =>
			block.name === 'core/group' &&
			block.innerBlocks.some(
				( innerBlock ) =>
					innerBlock.name === 'woocommerce/store-notices'
			)
	);

	if ( groupBlock ) {
		selectBlock( groupBlock.clientId );
	}
};

const getDescription = () => {
	return __(
		'This block represents the classic template used to display the order confirmation. The actual rendered template may appear different from this placeholder.',
		'woocommerce'
	);
};

const getSkeleton = () => {
	return (
		<div className="woocommerce-page">
			<div className="woocommerce-order">
				<h1>{ __( 'Order received', 'woocommerce' ) }</h1>
				<p className="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-confirmation">
					{ __(
						'Thank you. Your order has been received.',
						'woocommerce'
					) }
				</p>
				<ul className="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
					<li className="woocommerce-order-overview__order order">
						{ __( 'Order number', 'woocommerce' ) }:{ ' ' }
						<strong>123</strong>
					</li>
					<li className="woocommerce-order-overview__date date">
						{ __( 'Date', 'woocommerce' ) }:{ ' ' }
						<strong>May 25, 2023</strong>
					</li>
					<li className="woocommerce-order-overview__email email">
						{ __( 'Email', 'woocommerce' ) }:{ ' ' }
						<strong>shopper@woocommerce.com</strong>
					</li>
					<li className="woocommerce-order-overview__total total">
						{ __( 'Total', 'woocommerce' ) }:{ ' ' }
						<strong>$20.00</strong>
					</li>
				</ul>

				<section className="woocommerce-order-details">
					<h2 className="woocommerce-order-details__title">
						{ __( 'Order details', 'woocommerce' ) }
					</h2>
					<table className="woocommerce-table woocommerce-table--order-details shop_table order_details">
						<thead>
							<tr>
								<th className="woocommerce-table__product-name product-name">
									{ __( 'Product', 'woocommerce' ) }
								</th>
								<th className="woocommerce-table__product-table product-total">
									{ __( 'Total', 'woocommerce' ) }
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
									{ __( 'Subtotal', 'woocommerce' ) }:
								</th>
								<td>$20.00</td>
							</tr>
							<tr>
								<th scope="row">
									{ __( 'Total', 'woocommerce' ) }:
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
								{ __( 'Billing address', 'woocommerce' ) }
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
								{ __( 'Shipping address', 'woocommerce' ) }
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

const blockifyConfig = {
	getButtonLabel,
	onClickCallback,
	getBlockifiedTemplate,
};

export { blockifyConfig, isConversionPossible, getDescription, getSkeleton };
