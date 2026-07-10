/**
 * External dependencies
 */
import { RangeControl } from '@wordpress/components';

export const MIN_PRODUCTS_PER_PAGE = 1;
export const MAX_PRODUCTS_PER_PAGE = 100;

const ProductsPerPageRangeControl = ( {
	label,
	value,
	onChange,
}: {
	label: string;
	value: number;
	onChange: ( newPerPage: number ) => void;
} ) => {
	return (
		<RangeControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ label }
			min={ MIN_PRODUCTS_PER_PAGE }
			max={ MAX_PRODUCTS_PER_PAGE }
			onChange={ ( newPerPage: number ) => {
				if (
					newPerPage < MIN_PRODUCTS_PER_PAGE ||
					newPerPage > MAX_PRODUCTS_PER_PAGE
				) {
					return;
				}
				onChange( newPerPage );
			} }
			value={ value }
		/>
	);
};

export default ProductsPerPageRangeControl;
