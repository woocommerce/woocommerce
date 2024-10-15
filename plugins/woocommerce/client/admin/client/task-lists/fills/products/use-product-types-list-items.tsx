/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { useCreateProductByType } from './use-create-product-by-type';
import { ProductType, ProductTypeKey } from './constants';

type ProductTypeWithOnclick = ProductType & {
	onClick?: () => void;
};

const useProductTypeListItems = (
	_productTypes: ProductType[],
	suggestedProductTypes: ProductTypeKey[] = [],
	{
		onClick,
	}: {
		onClick?: () => void;
	} = {}
) => {
	const { createProductByType, isRequesting } = useCreateProductByType();

	const productTypes = useMemo(
		() =>
			_productTypes.map( ( productType: ProductTypeWithOnclick ) => ( {
				...productType,
				onClick: () => {
					if ( typeof productType?.onClick === 'function' ) {
						productType.onClick();
					} else {
						createProductByType( productType.key );
					}
					recordEvent( 'tasklist_add_product', {
						method: 'product_template',
					} );
					recordEvent( 'tasklist_product_template_selection', {
						product_type: productType.key,
						is_suggested: suggestedProductTypes.includes(
							productType.key
						),
					} );
					if ( typeof onClick === 'function' ) {
						onClick();
					}
				},
			} ) ),
		[ _productTypes, createProductByType, onClick, suggestedProductTypes ]
	);

	return { productTypes, isRequesting };
};

export default useProductTypeListItems;
