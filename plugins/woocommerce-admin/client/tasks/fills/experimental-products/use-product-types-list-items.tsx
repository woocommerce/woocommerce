/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useCreateProductByType from './use-create-product-by-type';
import { ProductType } from './constants';

const useProductTypeListItems = ( _productTypes: ProductType[] ) => {
	const { createProductByType } = useCreateProductByType();

	const productTypes = useMemo(
		() =>
			_productTypes.map( ( productType ) => ( {
				...productType,
				onClick: () => createProductByType( productType.key ),
			} ) ),
		[ createProductByType ]
	);

	return productTypes;
};

export default useProductTypeListItems;
