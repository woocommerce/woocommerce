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
import { MouseEvent } from 'react';

/**
 * Internal dependencies
 */
import { usePublish } from '../hooks/use-publish';

export function PublishButton(
	props: Omit< Button.ButtonProps, 'aria-disabled' | 'variant' | 'children' >
) {
	const [ productStatus ] = useEntityProp< ProductStatus | 'auto-draft' >(
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
