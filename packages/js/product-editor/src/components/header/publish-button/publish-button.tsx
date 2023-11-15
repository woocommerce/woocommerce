/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { MouseEvent } from 'react';
import { Product } from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getProductErrorMessage } from '../../../utils/get-product-error-message';
import { recordProductEvent } from '../../../utils/record-product-event';
import { usePublish } from '../hooks/use-publish';
import { PublishButtonProps } from './types';
import { useFeedbackBar } from '../../../hooks/use-feedback-bar';

export function PublishButton( {
	productStatus,
	productType = 'product',
	...props
}: PublishButtonProps ) {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const { maybeShowFeedbackBar } = useFeedbackBar();

	const publishButtonProps = usePublish( {
		productType,
		productStatus,
		...props,
		onPublishSuccess( savedProduct: Product ) {
			const isPublished =
				productType === 'product' ? productStatus === 'publish' : true;

			if ( isPublished ) {
				recordProductEvent( 'product_update', savedProduct );
			}

			const noticeContent = isPublished
				? __( 'Product updated.', 'woocommerce' )
				: __( 'Product added.', 'woocommerce' );
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

			maybeShowFeedbackBar();

			if ( productStatus === 'auto-draft' ) {
				const url = getNewPath( {}, `/product/${ savedProduct.id }` );
				navigateTo( { url } );
			}
		},
		onPublishError( error ) {
			const message = getProductErrorMessage( error );
			createErrorNotice( message );
		},
	} );

	return <Button { ...publishButtonProps } />;
}
