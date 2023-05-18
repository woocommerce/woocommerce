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
import { getProductErrorMessage } from '../../../utils/get-product-error-message';
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

	const isPublished = productStatus === 'publish';

	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const publishButtonProps = usePublish( {
		...props,
		onPublishSuccess( savedProduct: Product ) {
			recordProductEvent( 'product_update', savedProduct );

			const noticeContent = isPublished
				? __( 'Product published.', 'woocommerce' )
				: __( 'Product successfully created.', 'woocommerce' );
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
		onPublishError( error ) {
			const defaultMessage = isPublished
				? __( 'Failed to publish product.', 'woocommerce' )
				: __( 'Failed to create product.', 'woocommerce' );

			const message = getProductErrorMessage( error );
			createErrorNotice( message || defaultMessage );
		},
	} );

	return <Button { ...publishButtonProps } />;
}
