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
import { CoreSelectors } from '../types';

export function useConfirmUnsavedProductChanges() {
	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);
	const { hasEdits } = useProductEdits();
	const { isSaving } = useSelect(
		( select ) => {
			const { isSavingEntityRecord }: CoreSelectors = select( 'core' );

			return {
				isSaving: isSavingEntityRecord(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	useConfirmUnsavedChanges( hasEdits || isSaving, preventLeavingProductForm );
}
