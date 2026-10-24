/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { Spinner } from '@wordpress/components';

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
	const { createProductByType, requestingType } = useCreateProductByType();

	const productTypes = useMemo(
		() =>
			_productTypes.map( ( productType: ProductTypeWithOnclick ) => ( {
				...productType,
				...( requestingType === productType.key && {
					after: <Spinner />,
				} ),
				onClick: () => {
					if ( requestingType ) {
						return;
					}
					if ( typeof productType?.onClick === 'function' ) {
						productType.onClick();
					} else {
						void createProductByType( productType.key );
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
				// The cast lets `after` hold the spinner; the literal item types only allow their own `after`.
			} ) ) as Array< ProductType & { onClick: () => void } >,
		[
			_productTypes,
			createProductByType,
			onClick,
			suggestedProductTypes,
			requestingType,
		]
	);

	return { productTypes };
};

export default useProductTypeListItems;
