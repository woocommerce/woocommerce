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

/**
 * Internal dependencies
 */
import { useProductManager } from '../../../../hooks/use-product-manager';
import { useProductScheduled } from '../../../../hooks/use-product-scheduled';
import { recordProductEvent } from '../../../../utils/record-product-event';
import { getProductErrorMessage } from '../../../../utils/get-product-error-message';
import { ButtonWithDropdownMenu } from '../../../button-with-dropdown-menu';
import { SchedulePublishModal } from '../../../schedule-publish-modal';
import { showSuccessNotice } from '../utils';
import type { PublishButtonMenuProps } from './types';

export function PublishButtonMenu( {
	postType,
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

	function scheduleProduct( dateString?: string ) {
		schedule( dateString )
			.then( ( scheduledProduct ) => {
				recordProductEvent( 'product_schedule', scheduledProduct );

				showSuccessNotice( scheduledProduct );
			} )
			.catch( ( error ) => {
				const message = getProductErrorMessage( error );
				createErrorNotice( message );
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
												'Product successfully deleted',
												'woocommerce'
											)
										);
										const url = getNewPath(
											{},
											`/product/${ duplicatedProduct.id }`
										);
										navigateTo( { url } );
									} )
									.catch( ( error ) => {
										const message =
											getProductErrorMessage( error );
										createErrorNotice( message );
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
									.catch( ( error ) => {
										const message =
											getProductErrorMessage( error );
										createErrorNotice( message );
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
			<ButtonWithDropdownMenu { ...props } renderMenu={ renderMenu } />

			{ renderSchedulePublishModal() }
		</>
	);
}
