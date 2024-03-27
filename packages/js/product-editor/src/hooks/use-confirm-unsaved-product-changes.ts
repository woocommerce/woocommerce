/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useConfirmUnsavedChanges } from '@woocommerce/navigation';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { preventLeavingProductForm } from '../utils/prevent-leaving-product-form';
import { useProductEdits } from './use-product-edits';

export function useConfirmUnsavedProductChanges(
	productType = <string>'product'
) {
	const [ productId ] = useEntityProp< number >(
		'postType',
		productType,
		'id'
	);
	const { hasEdits } = useProductEdits( productType );
	const { isSaving } = useSelect(
		( select ) => {
			const { isSavingEntityRecord } = select( 'core' );

			return {
				isSaving: isSavingEntityRecord< boolean >(
					'postType',
					productType,
					productId
				),
			};
		},
		[ productId, productType ]
	);

	useConfirmUnsavedChanges(
		hasEdits || isSaving,
		preventLeavingProductForm( productId )
	);
}
