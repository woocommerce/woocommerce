/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import QuantitySelector from '@woocommerce/base-components/quantity-selector';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { getCurrency } from '@woocommerce/base-utils';

const ProductVariationDetails = ( { variation } ) => {
	const variationsText = variation
		.map( ( v ) => `${ v.attribute }: ${ v.value }` )
		.join( ' / ' );

	return (
		<div className="wc-block-cart-item__product-attributes">
			{ variationsText }
		</div>
	);
};

const CartLineItemRow = ( { lineItem } ) => {
	const { name, description, images, variation, quantity, totals } = lineItem;
	const { line_total: total, line_subtotal: subtotal } = totals;

	const imageProps = {};
	if ( images && images.length ) {
		imageProps.src = lineItem.images[ 0 ].src || '';
		imageProps.alt = lineItem.images[ 0 ].alt || '';
		imageProps.srcSet = lineItem.images[ 0 ].srcset || '';
		imageProps.sizes = lineItem.images[ 0 ].sizes || '';
	}

	const [ lineQuantity, setLineQuantity ] = useState( quantity );
	const currency = getCurrency();

	const isDiscounted = subtotal !== total;
	const fullPrice = isDiscounted ? (
		<div className="wc-block-cart-item__full-price">
			<FormattedMonetaryAmount currency={ currency } value={ subtotal } />
		</div>
	) : null;

	// We use this in two places so we can stack the quantity selector under
	// product info on smaller screens.
	const quantitySelector = ( className ) => {
		return (
			<QuantitySelector
				className={ className }
				quantity={ lineQuantity }
				onChange={ setLineQuantity }
			/>
		);
	};

	return (
		<tr>
			<td className="wc-block-cart-item__product">
				<div className="wc-block-cart-item__product-wrapper">
					<img { ...imageProps } alt={ imageProps.alt } />
					<div className="wc-block-cart-item__product-details">
						<div className="wc-block-cart-item__product-name">
							{ name }
						</div>
						<div className="wc-block-cart-item__product-metadata">
							{ description }
							<ProductVariationDetails variation={ variation } />
						</div>
						{ quantitySelector(
							'wc-block-cart-item__quantity-stacked'
						) }
					</div>
				</div>
			</td>
			<td className="wc-block-cart-item__quantity">
				<div>
					{ quantitySelector() }
					<div className="wc-block-cart-item__remove-link">
						{ __( 'Remove item', 'woo-gutenberg-products-block' ) }
					</div>
				</div>
			</td>
			<td className="wc-block-cart-item__total">
				{ fullPrice }
				<FormattedMonetaryAmount
					currency={ currency }
					value={ total }
				/>
			</td>
		</tr>
	);
};

CartLineItemRow.propTypes = {
	lineItem: PropTypes.shape( {
		name: PropTypes.string.isRequired,
		description: PropTypes.string.isRequired,
		images: PropTypes.array.isRequired,
		quantity: PropTypes.number.isRequired,
		variation: PropTypes.arrayOf(
			PropTypes.shape( {
				attribute: PropTypes.string.isRequired,
				value: PropTypes.string.isRequired,
			} )
		).isRequired,
		totals: PropTypes.shape( {
			line_subtotal: PropTypes.string.isRequired,
			line_total: PropTypes.string.isRequired,
		} ).isRequired,
	} ),
};

export default CartLineItemRow;
