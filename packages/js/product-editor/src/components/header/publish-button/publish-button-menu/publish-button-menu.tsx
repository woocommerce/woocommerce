/**
 * External dependencies
 */
import { Dropdown, MenuGroup, MenuItem } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch } from '@wordpress/data';
import { createElement, Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ProductStatus } from '@woocommerce/data';
import { getNewPath, navigateTo } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { useProductManager } from '../../../../hooks/use-product-manager';
import { useProductScheduled } from '../../../../hooks/use-product-scheduled';
import { recordProductEvent } from '../../../../utils/record-product-event';
import { useErrorHandler } from '../../../../hooks/use-error-handler';
import { ButtonWithDropdownMenu } from '../../../button-with-dropdown-menu';
import { SchedulePublishModal } from '../../../schedule-publish-modal';
import { showSuccessNotice } from '../utils';
import type { PublishButtonMenuProps } from './types';
import { TRACKS_SOURCE } from '../../../../constants';

export function PublishButtonMenu( {
	postType,
	visibleTab = 'general',
	...props
}: PublishButtonMenuProps ) {
	const { isScheduling, isScheduled, schedule, date, formattedDate } =
		useProductScheduled( postType );
	const [ showScheduleModal, setShowScheduleModal ] = useState<
		'schedule' | 'edit' | undefined
	>();
	const { copyToDraft, trash } = useProductManager( postType );
	const { createErrorNotice, createSuccessNotice } =
		useDispatch( 'core/notices' );
	const [ , , prevStatus ] = useEntityProp< ProductStatus >(
		'postType',
		postType,
		'status'
	);
	const { getProductErrorMessageAndProps } = useErrorHandler();

	function scheduleProduct( dateString?: string ) {
		schedule( dateString )
			.then( ( scheduledProduct ) => {
				recordProductEvent( 'product_schedule', scheduledProduct );

				showSuccessNotice( scheduledProduct );
			} )
			.catch( async ( error ) => {
				const { message, errorProps } =
					await getProductErrorMessageAndProps( error, visibleTab );
				createErrorNotice( message, errorProps );
			} )
			.finally( () => {
				setShowScheduleModal( undefined );
			} );
	}

	function renderSchedulePublishModal() {
		return (
			showScheduleModal && (
				<SchedulePublishModal
					postType={ postType }
					value={ showScheduleModal === 'edit' ? date : undefined }
					isScheduling={ isScheduling }
					onCancel={ () => setShowScheduleModal( undefined ) }
					onSchedule={ scheduleProduct }
				/>
			)
		);
	}

	function renderMenu( { onClose }: Dropdown.RenderProps ) {
		return (
			<>
				<MenuGroup>
					{ isScheduled ? (
						<>
							<MenuItem
								onClick={ () => {
									scheduleProduct();
									onClose();
								} }
							>
								{ __( 'Publish now', 'woocommerce' ) }
							</MenuItem>
							<MenuItem
								info={ formattedDate }
								onClick={ () => {
									setShowScheduleModal( 'edit' );
									onClose();
								} }
							>
								{ __( 'Edit schedule', 'woocommerce' ) }
							</MenuItem>
						</>
					) : (
						<MenuItem
							onClick={ () => {
								recordEvent( 'product_schedule_publish', {
									source: TRACKS_SOURCE,
								} );
								setShowScheduleModal( 'schedule' );
								onClose();
							} }
						>
							{ __( 'Schedule publish', 'woocommerce' ) }
						</MenuItem>
					) }
				</MenuGroup>

				{ prevStatus !== 'trash' && (
					<MenuGroup>
						<MenuItem
							onClick={ () => {
								copyToDraft()
									.then( ( duplicatedProduct ) => {
										recordProductEvent(
											'product_copied_to_draft',
											duplicatedProduct
										);
										createSuccessNotice(
											__(
												'Product successfully duplicated',
												'woocommerce'
											)
										);
										const url = getNewPath(
											{},
											`/product/${ duplicatedProduct.id }`
										);
										navigateTo( { url } );
									} )
									.catch( async ( error ) => {
										const { message, errorProps } =
											await getProductErrorMessageAndProps(
												error,
												visibleTab
											);
										createErrorNotice(
											message,
											errorProps
										);
									} );
								onClose();
							} }
						>
							{ __( 'Copy to a new draft', 'woocommerce' ) }
						</MenuItem>
						<MenuItem
							isDestructive
							onClick={ () => {
								trash()
									.then( ( deletedProduct ) => {
										recordProductEvent(
											'product_delete',
											deletedProduct
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
									.catch( async ( error ) => {
										const { message, errorProps } =
											await getProductErrorMessageAndProps(
												error,
												visibleTab
											);
										createErrorNotice(
											message,
											errorProps
										);
									} );
								onClose();
							} }
						>
							{ __( 'Move to trash', 'woocommerce' ) }
						</MenuItem>
					</MenuGroup>
				) }
			</>
		);
	}

	return (
		<>
			<ButtonWithDropdownMenu
				{ ...props }
				onToggle={ ( isOpen: boolean ) => {
					if ( isOpen ) {
						recordEvent( 'product_publish_dropdown_open', {
							source: TRACKS_SOURCE,
						} );
					}
					props.onToggle?.( isOpen );
				} }
				renderMenu={ renderMenu }
			/>

			{ renderSchedulePublishModal() }
		</>
	);
}
