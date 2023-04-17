/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useSaveDraft } from '../hooks/use-save-draft';

export function SaveDraftButton(
	props: Omit< Button.ButtonProps, 'aria-disabled' | 'variant' | 'children' >
) {
	const productId = useEntityId( 'postType', 'product' );
	const product: Product = useSelect( ( select ) =>
		select( 'core' ).getEditedEntityRecord(
			'postType',
			'product',
			productId
		)
	);
	const { downloadable, id, manage_stock, status, type, virtual } = product;

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const saveDraftButtonProps = useSaveDraft( {
		...props,
		onSaveSuccess( savedProduct: Product ) {
			recordEvent( 'product_edit', {
				new_product_page: true,
				product_id: id,
				product_type: type,
				is_downloadable: downloadable,
				is_virtual: virtual,
				manage_stock,
			} );

			createSuccessNotice(
				__( 'Product saved as draft.', 'woocommerce' )
			);

			if ( status === 'auto-draft' ) {
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
