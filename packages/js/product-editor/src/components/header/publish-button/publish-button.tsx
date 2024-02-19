/**
 * External dependencies
 */
import { MouseEvent, useState } from 'react';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { createElement, Fragment } from '@wordpress/element';
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
import { TRACKS_SOURCE } from '../../../constants';
import { ButtonWithDropdownMenu } from '../../button-with-dropdown-menu';
import { usePublish } from '../hooks/use-publish';
import { PublishButtonProps } from './types';
import { useProductScheduled } from '../../../hooks/use-product-scheduled';
import { SchedulePublishModal } from '../../schedule-publish-modal';

export function PublishButton( {
	productType = 'product',
	prePublish,
	...props
}: PublishButtonProps ) {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );

	const { maybeShowFeedbackBar } = useFeedbackBar();
	const { openPrepublishPanel } = useDispatch( productEditorUiStore );

	const [ editedStatus, , prevStatus ] = useEntityProp< Product[ 'status' ] >(
		'postType',
		productType,
		'status'
	);

	const { publish, ...publishButtonProps } = usePublish( {
		productType,
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

			if ( prevStatus === 'auto-draft' ) {
				const url = getNewPath( {}, `/product/${ savedProduct.id }` );
				navigateTo( { url } );
			}
		},
		onPublishError( error ) {
			const message = getProductErrorMessage( error );
			createErrorNotice( message );
		},
	} );

	const { isScheduled, editDate, date } = useProductScheduled( productType );
	const [ showScheduleModal, setShowScheduleModal ] = useState( false );

	if ( window.wcAdminFeatures[ 'product-pre-publish-modal' ] && prePublish ) {
		function getPublishButtonControls() {
			return [
				isScheduled
					? [
							{
								title: __( 'Publish now', 'woocommerce' ),
								async onClick() {
									await editDate( new Date().toISOString() );
									await publish();
								},
							},
							{
								title: __( 'Edit schedule', 'woocommerce' ),
								onClick() {
									setShowScheduleModal( true );
								},
							},
					  ]
					: [
							{
								title: __( 'Schedule publish', 'woocommerce' ),
								onClick() {
									setShowScheduleModal( true );
								},
							},
					  ],
			];
		}

		function renderSchedulePublishModal() {
			return (
				showScheduleModal && (
					<SchedulePublishModal
						value={ date }
						onCancel={ () => setShowScheduleModal( false ) }
						onSchedule={ async ( value ) => {
							await editDate( value );
							await publish();
							setShowScheduleModal( false );
						} }
					/>
				)
			);
		}

		if ( editedStatus !== 'publish' && editedStatus !== 'future' ) {
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
				<>
					<ButtonWithDropdownMenu
						{ ...publishButtonProps }
						onClick={ handlePrePublishButtonClick }
						controls={ getPublishButtonControls() }
					/>

					{ renderSchedulePublishModal() }
				</>
			);
		}

		return (
			<>
				<ButtonWithDropdownMenu
					{ ...publishButtonProps }
					controls={ getPublishButtonControls() }
				/>

				{ renderSchedulePublishModal() }
			</>
		);
	}

	return <Button { ...publishButtonProps } />;
}
