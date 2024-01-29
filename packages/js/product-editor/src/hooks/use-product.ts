/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { Product, ReadOnlyProperties } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';


export function useProduct() {
	const { editEntityRecord, saveEntityRecord } = useDispatch( 'core' );

	const productId = useEntityId( 'postType', 'product' );

    function editProduct( data: Omit< Product, ReadOnlyProperties > ) {
        return editEntityRecord(
            'postType',
            'product',
            productId,
            applyFilters(
                'woocommerce_edit_product_data',
                data,
                productId
            )
        );
    }

    function saveProduct( data: Omit< Product, ReadOnlyProperties > ) {
        return saveEntityRecord( 'postType', 'product', {
            id: productId,
            ...( applyFilters(
                'woocommerce_save_product_data',
                data,
                productId
            ) as Omit< Product, ReadOnlyProperties > )
        } );
    }

    return {
        editProduct,
        saveProduct,
    }
}
