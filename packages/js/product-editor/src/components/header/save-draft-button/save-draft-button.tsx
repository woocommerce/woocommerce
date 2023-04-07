/**
 * External dependencies
 */
import { Product, ProductStatus } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useSaveDraft } from '../hooks/use-save-draft';

export function SaveDraftButton(
	props: Omit< Button.ButtonProps, 'aria-disabled' | 'variant' | 'children' >
) {
	const [ productStatus ] = useEntityProp< ProductStatus | 'auto-draft' >(
		'postType',
		'product',
		'status'
	);

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const saveDraftButtonProps = useSaveDraft( {
		...props,
		onSaveSuccess( savedProduct: Product ) {
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
