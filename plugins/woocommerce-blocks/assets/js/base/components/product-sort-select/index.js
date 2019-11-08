/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import SortSelect from '@woocommerce/base-components/sort-select';

/**
 * Internal dependencies
 */
import './style.scss';

const ProductSortSelect = ( { defaultValue, onChange, readOnly, value } ) => {
	return (
		<SortSelect
			className="wc-block-product-sort-select"
			defaultValue={ defaultValue }
			name="orderby"
			onChange={ onChange }
			options={ [
				{
					key: 'menu_order',
					label: __(
						'Default sorting',
						'woo-gutenberg-products-block'
					),
				},
				{
					key: 'popularity',
					label: __( 'Popularity', 'woo-gutenberg-products-block' ),
				},
				{
					key: 'rating',
					label: __(
						'Average rating',
						'woo-gutenberg-products-block'
					),
				},
				{
					key: 'date',
					label: __( 'Latest', 'woo-gutenberg-products-block' ),
				},
				{
					key: 'price',
					label: __(
						'Price: low to high',
						'woo-gutenberg-products-block'
					),
				},
				{
					key: 'price-desc',
					label: __(
						'Price: high to low',
						'woo-gutenberg-products-block'
					),
				},
			] }
			readOnly={ readOnly }
			screenReaderLabel={ __(
				'Order products by',
				'woo-gutenberg-products-block'
			) }
			value={ value }
		/>
	);
};

ProductSortSelect.propTypes = {
	defaultValue: PropTypes.oneOf( [
		'menu_order',
		'popularity',
		'rating',
		'date',
		'price',
		'price-desc',
	] ),
	onChange: PropTypes.func,
	readOnly: PropTypes.bool,
	value: PropTypes.oneOf( [
		'menu_order',
		'popularity',
		'rating',
		'date',
		'price',
		'price-desc',
	] ),
};

export default ProductSortSelect;
