/**
 * External dependencies
 */
import type { MouseEvent } from 'react';
import { Button, Dropdown } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { type Product } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { store as productEditorUiStore } from '../../../store/product-editor-ui';
import { useErrorHandler } from '../../../hooks/use-error-handler';
import { recordProductEvent } from '../../../utils/record-product-event';
import { useFeedbackBar } from '../../../hooks/use-feedback-bar';
import { TRACKS_SOURCE } from '../../../constants';
import { usePublish } from '../hooks/use-publish';
import { PublishButtonMenu } from './publish-button-menu';
import { showSuccessNotice } from './utils';
import type { PublishButtonProps } from './types';

export function PublishButton( {
	productType = 'product',
	isMenuButton,
	isPrePublishPanelVisible = true,
	visibleTab = 'general',
	...props
}: PublishButtonProps ) {
	const { createErrorNotice } = useDispatch( 'core/notices' );
	const { maybeShowFeedbackBar } = useFeedbackBar();
	const { openPrepublishPanel } = useDispatch( productEditorUiStore );
	const { getProductErrorMessageAndProps } = useErrorHandler();

	const [ editedStatus, , prevStatus ] = useEntityProp< Product[ 'status' ] >(
		'postType',
		productType,
		'status'
	);

	const publishButtonProps = usePublish( {
		productType,
		...props,
		onPublishSuccess( savedProduct: Product ) {
			const isPublished =
				savedProduct.status === 'publish' ||
				savedProduct.status === 'future';

			if ( isPublished ) {
				recordProductEvent( 'product_update', savedProduct );
			}

			showSuccessNotice( savedProduct, prevStatus );

			maybeShowFeedbackBar();

			if ( prevStatus === 'auto-draft' || prevStatus === 'draft' ) {
				const url = getNewPath( {}, `/product/${ savedProduct.id }` );
				navigateTo( { url } );
			}
		},
		async onPublishError( error ) {
			const { message, errorProps } =
				await getProductErrorMessageAndProps( error, visibleTab );
			createErrorNotice( message, errorProps );
		},
	} );

	if ( productType === 'product' && isMenuButton ) {
		function renderPublishButtonMenu(
			menuProps: Dropdown.RenderProps
		): React.ReactElement {
			return (
				<PublishButtonMenu
					{ ...menuProps }
					postType={ productType }
					visibleTab={ visibleTab }
				/>
			);
		}

		if (
			editedStatus !== 'publish' &&
			editedStatus !== 'future' &&
			window.wcAdminFeatures[ 'product-pre-publish-modal' ] &&
			isPrePublishPanelVisible
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
				<PublishButtonMenu
					{ ...publishButtonProps }
					postType={ productType }
					controls={ undefined }
					onClick={ handlePrePublishButtonClick }
					renderMenu={ renderPublishButtonMenu }
					visibleTab={ visibleTab }
				/>
			);
		}

		return (
			<PublishButtonMenu
				{ ...publishButtonProps }
				postType={ productType }
				controls={ undefined }
				renderMenu={ renderPublishButtonMenu }
				visibleTab={ visibleTab }
			/>
		);
	}

	return <Button { ...publishButtonProps } />;
}
