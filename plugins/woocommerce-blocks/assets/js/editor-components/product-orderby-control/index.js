/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * A pre-configured SelectControl for product orderby settings.
 *
 * @param {Object}            props               Incoming props for the component.
 * @param {string}            props.value
 * @param {function(any):any} props.setAttributes Setter for block attributes.
 */
const ProductOrderbyControl = ( { value, setAttributes } ) => {
	return (
		<SelectControl
			label={ __( 'Order products by', 'woo-gutenberg-products-block' ) }
			value={ value }
			options={ [
				{
					label: __(
						'Newness - newest first',
						'woo-gutenberg-products-block'
					),
					value: 'date',
				},
				{
					label: __(
						'Price - low to high',
						'woo-gutenberg-products-block'
					),
					value: 'price_asc',
				},
				{
					label: __(
						'Price - high to low',
						'woo-gutenberg-products-block'
					),
					value: 'price_desc',
				},
				{
					label: __(
						'Rating - highest first',
						'woo-gutenberg-products-block'
					),
					value: 'rating',
				},
				{
					label: __(
						'Sales - most first',
						'woo-gutenberg-products-block'
					),
					value: 'popularity',
				},
				{
					label: __(
						'Title - alphabetical',
						'woo-gutenberg-products-block'
					),
					value: 'title',
				},
				{
					label: __( 'Menu Order', 'woo-gutenberg-products-block' ),
					value: 'menu_order',
				},
			] }
			onChange={ ( orderby ) => setAttributes( { orderby } ) }
		/>
	);
};

ProductOrderbyControl.propTypes = {
	/**
	 * Callback to update the order setting.
	 */
	setAttributes: PropTypes.func.isRequired,
	/**
	 * The selected order setting.
	 */
	value: PropTypes.string.isRequired,
};

export default ProductOrderbyControl;
