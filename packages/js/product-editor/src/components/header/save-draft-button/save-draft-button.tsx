/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { Product, ProductStatus } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { recordProductEvent } from '../../../utils/record-product-event';
import { useSaveDraft } from '../hooks/use-save-draft';

export function SaveDraftButton(
	props: Omit< Button.ButtonProps, 'aria-disabled' | 'variant' | 'children' >
) {
	const [ productStatus ] = useEntityProp< ProductStatus >(
		'postType',
		'product',
		'status'
	);

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const saveDraftButtonProps = useSaveDraft( {
		...props,
		onSaveSuccess( savedProduct: Product ) {
			recordProductEvent( 'product_edit', savedProduct );

			createSuccessNotice(
				__( 'Product saved as draft.', 'woocommerce' )
			);

			if ( productStatus === 'auto-draft' ) {
				const url = getNewPath( {}, `/product/${ savedProduct.id }` );
				navigateTo( { url } );
			}
		},
		onSaveError() {
			createErrorNotice(
				__( 'Failed to update product.', 'woocommerce' )
			);
		},
	} );

	return <Button { ...saveDraftButtonProps } />;
}
