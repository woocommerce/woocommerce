/**
 * External dependencies
 */
import { MouseEvent, useState } from 'react';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { dispatch, useDispatch } from '@wordpress/data';
import { createElement, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { type Product } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink } from '@woocommerce/settings';

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
import { formatScheduleDatetime } from '../../../utils';

function getNoticeContent( product: Product, prevStatus: Product[ 'status' ] ) {
	if (
		window.wcAdminFeatures[ 'product-pre-publish-modal' ] &&
		product.status === 'future'
	) {
		return sprintf(
			// translators: %s: The datetime the product is scheduled for.
			__( 'Product scheduled for %s.', 'woocommerce' ),
			formatScheduleDatetime( product.date_created )
		);
	}

	if ( prevStatus === 'publish' || prevStatus === 'future' ) {
		return __( 'Product updated.', 'woocommerce' );
	}

	return __( 'Product published.', 'woocommerce' );
}

function showSuccessNotice(
	product: Product,
	prevStatus: Product[ 'status' ]
) {
	const { createSuccessNotice } = dispatch( 'core/notices' );

	const noticeContent = getNoticeContent( product, prevStatus );
	const noticeOptions = {
		icon: '🎉',
		actions: [
			{
				label: __( 'View in store', 'woocommerce' ),
				// Leave the url to support a11y.
				url: product.permalink,
				onClick( event: MouseEvent< HTMLAnchorElement > ) {
					event.preventDefault();
					// Notice actions do not support target anchor prop,
					// so this forces the page to be opened in a new tab.
					window.open( product.permalink, '_blank' );
				},
			},
		],
	};

	createSuccessNotice( noticeContent, noticeOptions );
}

export function PublishButton( {
	productType = 'product',
	prePublish,
	...props
}: PublishButtonProps ) {
	const { createErrorNotice, createSuccessNotice } =
		useDispatch( 'core/notices' );
	const { maybeShowFeedbackBar } = useFeedbackBar();
	const { openPrepublishPanel } = useDispatch( productEditorUiStore );

	const [ editedStatus, , prevStatus ] = useEntityProp< Product[ 'status' ] >(
		'postType',
		productType,
		'status'
	);

	const { publish, deleteProduct, ...publishButtonProps } = usePublish( {
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

	const { isScheduled, schedule, date, formattedDate } =
		useProductScheduled( productType );
	const [ showScheduleModal, setShowScheduleModal ] = useState<
		'schedule' | 'edit' | undefined
	>();

	if (
		productType === 'product' &&
		window.wcAdminFeatures[ 'product-pre-publish-modal' ] &&
		prePublish
	) {
		function getPublishButtonControls() {
			return [
				isScheduled
					? [
							{
								title: __( 'Publish now', 'woocommerce' ),
								async onClick() {
									await schedule( publish );
								},
							},
							{
								title: (
									<div className="woocommerce-product-header__actions-edit-schedule">
										<div>
											{ __(
												'Edit schedule',
												'woocommerce'
											) }
										</div>
										<div>{ formattedDate }</div>
									</div>
								),
								onClick() {
									setShowScheduleModal( 'edit' );
								},
							},
					  ]
					: [
							{
								title: __( 'Schedule publish', 'woocommerce' ),
								onClick() {
									setShowScheduleModal( 'schedule' );
								},
							},
					  ],
				[
					{
						title: __( 'Move to trash', 'woocommerce' ),
						async onClick() {
							deleteProduct()
								.then( ( deletedProduct ) => {
									recordProductEvent(
										'product_delete',
										deletedProduct as Product
									);

									createSuccessNotice(
										__(
											'Product successfully deleted',
											'woocommerce'
										)
									);

									const productListUrl = getAdminLink(
										'edit.php?post_type=product'
									);
									navigateTo( {
										url: productListUrl,
									} );
								} )
								.catch( ( error ) => {
									const message =
										getProductErrorMessage( error );
									createErrorNotice( message );
								} );
						},
					},
				],
			];
		}

		function renderSchedulePublishModal() {
			return (
				showScheduleModal && (
					<SchedulePublishModal
						postType={ productType }
						value={
							showScheduleModal === 'edit' ? date : undefined
						}
						onCancel={ () => setShowScheduleModal( undefined ) }
						onSchedule={ async ( value ) => {
							await schedule( publish, value );
							setShowScheduleModal( undefined );
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
