/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { MouseEvent } from 'react';
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
import { usePublish } from '../hooks/use-publish';

export function PublishButton(
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

	const isCreating = status === 'auto-draft';

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const publishButtonProps = usePublish( {
		...props,
		onPublishSuccess( savedProduct: Product ) {
			recordEvent( 'product_update', {
				new_product_page: true,
				product_id: id,
				product_type: type,
				is_downloadable: downloadable,
				is_virtual: virtual,
				manage_stock,
			} );
			const noticeContent = isCreating
				? __( 'Product successfully created.', 'woocommerce' )
				: __( 'Product published.', 'woocommerce' );
			const noticeOptions = {
				icon: '🎉',
				actions: [
					{
						label: __( 'View in store', 'woocommerce' ),
						// Leave the url to support a11y.
						url: savedProduct.permalink,
						onClick( event: MouseEvent< HTMLAnchorElement > ) {
							event.preventDefault();
							// Notice actions do not support target anchor prop,
							// so this forces the page to be opened in a new tab.
							window.open( savedProduct.permalink, '_blank' );
						},
					},
				],
			};

			createSuccessNotice( noticeContent, noticeOptions );

			if ( status === 'auto-draft' ) {
				const url = getNewPath( {}, `/product/${ savedProduct.id }` );
				navigateTo( { url } );
			}
		},
		onPublishError() {
			const noticeContent = isCreating
				? __( 'Failed to create product.', 'woocommerce' )
				: __( 'Failed to publish product.', 'woocommerce' );

			createErrorNotice( noticeContent );
		},
	} );

	return <Button { ...publishButtonProps } />;
}
