/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { MouseEvent } from 'react';
import { Product, ProductStatus } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { recordProductEvent } from '../../../utils/record-product-event';
import { usePublish } from '../hooks/use-publish';

export function PublishButton(
	props: Omit< Button.ButtonProps, 'aria-disabled' | 'variant' | 'children' >
) {
	const [ productStatus ] = useEntityProp< ProductStatus >(
		'postType',
		'product',
		'status'
	);

	const isCreating = productStatus === 'auto-draft';

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const publishButtonProps = usePublish( {
		...props,
		onPublishSuccess( savedProduct: Product ) {
			recordProductEvent( 'product_update', savedProduct );

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

			if ( productStatus === 'auto-draft' ) {
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
