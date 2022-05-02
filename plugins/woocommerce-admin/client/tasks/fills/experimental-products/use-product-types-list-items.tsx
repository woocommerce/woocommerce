/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getProductTypes } from './utils';
import useCreateProductByType from './use-create-product-by-type';

const useProductTypeListItems = () => {
	const { createProductByType } = useCreateProductByType();

	const productTypes = useMemo(
		() =>
			getProductTypes().map( ( productType ) => ( {
				...productType,
				onClick: () => createProductByType( productType.key ),
			} ) ),
		[ createProductByType ]
	);

	return productTypes;
};

export default useProductTypeListItems;
