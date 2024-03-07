/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { ProductOrderbyControlProps } from './types';

/**
 * A pre-configured SelectControl for product orderby settings.
 *
 * @param {Object}            props               Incoming props for the component.
 * @param {string}            props.value
 * @param {function(any):any} props.setAttributes Setter for block attributes.
 */
const ProductOrderbyControl = ( {
	value,
	setAttributes,
}: ProductOrderbyControlProps ) => {
	return (
		<SelectControl
			label={ __( 'Order products by', 'woocommerce' ) }
			value={ value }
			options={ [
				{
					label: __( 'Newness - newest first', 'woocommerce' ),
					value: 'date',
				},
				{
					label: __( 'Price - low to high', 'woocommerce' ),
					value: 'price_asc',
				},
				{
					label: __( 'Price - high to low', 'woocommerce' ),
					value: 'price_desc',
				},
				{
					label: __( 'Rating - highest first', 'woocommerce' ),
					value: 'rating',
				},
				{
					label: __( 'Sales - most first', 'woocommerce' ),
					value: 'popularity',
				},
				{
					label: __( 'Title - alphabetical', 'woocommerce' ),
					value: 'title',
				},
				{
					label: __( 'Menu Order', 'woocommerce' ),
					value: 'menu_order',
				},
			] }
			onChange={ ( orderby ) => setAttributes( { orderby } ) }
		/>
	);
};

export default ProductOrderbyControl;
