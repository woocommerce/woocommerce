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
			const isPublished = productStatus === 'publish';

			if ( isPublished ) {
				let propsToRecord = savedProduct as Pick<
					Product,
					'type' | 'id'
				> &
					Partial< Product >;
				if ( savedProduct.parent_id > 0 ) {
					const { description, ...rest } = savedProduct;
					propsToRecord = {
						...rest,
						note: description,
					};
				}
				recordProductEvent( 'product_update', propsToRecord );
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
