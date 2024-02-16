/**
 * External dependencies
 */
import { MouseEvent } from 'react';
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { type Product } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { store as productEditorUiStore } from '../../../store/product-editor-ui';
import { getProductErrorMessage } from '../../../utils/get-product-error-message';
import { recordProductEvent } from '../../../utils/record-product-event';
import { useFeedbackBar } from '../../../hooks/use-feedback-bar';
import { usePublish } from '../hooks/use-publish';
import { TRACKS_SOURCE } from '../../../constants';
import { PublishButtonProps } from './types';

export function PublishButton( {
	productStatus,
	productType = 'product',
	prePublish,
	...props
}: PublishButtonProps ) {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const { maybeShowFeedbackBar } = useFeedbackBar();
	const { openPrepublishPanel } = useDispatch( productEditorUiStore );

	const { ...publishButtonProps } = usePublish( {
		productType,
		productStatus,
		...props,
		onPublishSuccess( savedProduct: Product ) {
			const isPublished =
				savedProduct.status === 'publish' ||
				savedProduct.status === 'future';

			if ( isPublished ) {
				recordProductEvent( 'product_update', savedProduct );
			}

			const noticeContent = isPublished
				? __( 'Product updated.', 'woocommerce' )
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

	if ( window.wcAdminFeatures[ 'product-pre-publish-modal' ] ) {
		if (
			prePublish &&
			productStatus !== 'publish' &&
			productStatus !== 'future'
		) {
			function handlePrePublishButtonClick(
				event: MouseEvent< HTMLButtonElement >
			) {
				if ( publishButtonProps[ 'aria-disabled' ] ) {
					event.preventDefault();
					return;
				}

				recordEvent( 'product_prepublish_panel', {
					source: TRACKS_SOURCE,
					action: 'view',
				} );
				openPrepublishPanel();
			}

			return (
				<Button
					{ ...publishButtonProps }
					onClick={ handlePrePublishButtonClick }
				/>
			);
		}
	}

	return <Button { ...publishButtonProps } />;
}
