/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { SortSelect } from '@woocommerce/blocks-components';

/**
 * Internal dependencies
 */
import './style.scss';
import { ProductSortSelectProps } from '../types';

const ProductSortSelect = ( {
	onChange,
	value,
}: ProductSortSelectProps ): JSX.Element => {
	return (
		<SortSelect
			className="wc-block-product-sort-select wc-block-components-product-sort-select"
			onChange={ onChange }
			options={ [
				{
					key: 'menu_order',
					label: __( 'Default sorting', 'woocommerce' ),
				},
				{
					key: 'popularity',
					label: __( 'Popularity', 'woocommerce' ),
				},
				{
					key: 'rating',
					label: __( 'Average rating', 'woocommerce' ),
				},
				{
					key: 'date',
					label: __( 'Latest', 'woocommerce' ),
				},
				{
					key: 'price',
					label: __( 'Price: low to high', 'woocommerce' ),
				},
				{
					key: 'price-desc',
					label: __( 'Price: high to low', 'woocommerce' ),
				},
			] }
			screenReaderLabel={ __( 'Order products by', 'woocommerce' ) }
			value={ value }
		/>
	);
};

export default ProductSortSelect;
