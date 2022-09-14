/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	ButtonGroup,
	DropdownMenu,
	MenuGroup,
	MenuItem,
} from '@wordpress/components';
import { chevronDown, check, Icon } from '@wordpress/icons';
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './product-form-actions.scss';
import { useProductHelper } from './use-product-helper';

export const ProductFormActions: React.FC = () => {
	const {
		createProductWithStatus,
		updateProductWithStatus,
		deleteProductAndRedirect,
		copyProductWithStatus,
		isUpdatingDraft,
		isUpdatingPublished,
		isDeleting,
	} = useProductHelper();
	const { isDirty, values, resetForm } = useFormContext< Product >();

	const getProductDataForTracks = () => {
		return {
			product_id: values.id,
			product_type: values.type,
			is_downloadable: values.downloadable,
			is_virtual: values.virtual,
			manage_stock: values.manage_stock,
		};
	};

	const onSaveDraft = async () => {
		recordEvent( 'product_edit', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( ! values.id ) {
			createProductWithStatus( values, 'draft' );
		} else {
			const product = await updateProductWithStatus(
				values.id,
				values,
				'draft'
			);
			if ( product && product.id ) {
				resetForm( product );
			}
		}
	};

	const onPublish = async () => {
		recordEvent( 'product_update', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( ! values.id ) {
			createProductWithStatus( values, 'publish' );
		} else {
			const product = await updateProductWithStatus(
				values.id,
				values,
				'publish'
			);
			if ( product && product.id ) {
				resetForm( product );
			}
		}
	};

	const onPublishAndDuplicate = async () => {
		recordEvent( 'product_publish_and_copy', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( values.id ) {
			await updateProductWithStatus( values.id, values, 'publish' );
		} else {
			await createProductWithStatus( values, 'publish', false, true );
		}
		await copyProductWithStatus( values );
	};

	const onCopyToNewDraft = async () => {
		recordEvent( 'product_copy', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( values.id ) {
			await updateProductWithStatus(
				values.id,
				values,
				values.status || 'draft'
			);
		}
		await copyProductWithStatus( values );
	};

	const onTrash = () => {
		recordEvent( 'product_delete', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( values.id ) {
			deleteProductAndRedirect( values.id );
		}
	};

	const isPublished = values.id && values.status === 'publish';

	return (
		<div className="woocommerce-product-form-actions">
			<Button
				onClick={ onSaveDraft }
				disabled={
					( ! isDirty &&
						!! values.id &&
						values.status !== 'publish' ) ||
					isUpdatingDraft ||
					isUpdatingPublished ||
					isDeleting
				}
			>
				{ ! isDirty && values.id && values.status !== 'publish' && (
					<Icon icon={ check } />
				) }
				{ isUpdatingDraft ? __( 'Saving', 'woocommerce' ) : null }
				{ ( isDirty || ! values.id ) &&
				! isUpdatingDraft &&
				values.status !== 'publish'
					? __( 'Save draft', 'woocommerce' )
					: null }
				{ values.status === 'publish' && ! isUpdatingDraft
					? __( 'Switch to draft', 'woocommerce' )
					: null }
				{ ! isDirty &&
				values.id &&
				! isUpdatingDraft &&
				values.status !== 'publish'
					? __( 'Saved', 'woocommerce' )
					: null }
			</Button>
			<Button
				onClick={ () =>
					recordEvent( 'product_preview_changes', {
						new_product_page: true,
						...getProductDataForTracks(),
					} )
				}
				href={ values.permalink + '?preview=true' }
				disabled={ ! values.permalink }
				target="_blank"
			>
				{ __( 'Preview', 'woocommerce' ) }
			</Button>
			<ButtonGroup className="woocommerce-product-form-actions__publish-button-group">
				<Button
					onClick={ onPublish }
					variant="primary"
					isBusy={ isUpdatingPublished }
					disabled={
						( ! isDirty && !! isPublished ) ||
						isUpdatingDraft ||
						isUpdatingPublished ||
						isDeleting
					}
				>
					{ isUpdatingPublished
						? __( 'Updating', 'woocommerce' )
						: null }
					{ isPublished && ! isUpdatingPublished
						? __( 'Update', 'woocommerce' )
						: null }
					{ ! isPublished && ! isUpdatingPublished
						? __( 'Publish', 'woocommerce' )
						: null }
				</Button>
				<DropdownMenu
					className="woocommerce-product-form-actions__publish-dropdown"
					label={ __( 'Publish options', 'woocommerce' ) }
					icon={ chevronDown }
					popoverProps={ { position: 'bottom left' } }
					toggleProps={ { variant: 'primary' } }
				>
					{ () => (
						<>
							<MenuGroup>
								<MenuItem onClick={ onPublishAndDuplicate }>
									{ isPublished
										? __(
												'Update & duplicate',
												'woocommerce'
										  )
										: __(
												'Publish & duplicate',
												'woocommerce'
										  ) }
								</MenuItem>
								<MenuItem onClick={ onCopyToNewDraft }>
									{ __(
										'Copy to a new draft',
										'woocommerce'
									) }
								</MenuItem>
								<MenuItem
									onClick={ onTrash }
									isDestructive
									disabled={ ! values.id }
								>
									{ __( 'Move to trash', 'woocommerce' ) }
								</MenuItem>
							</MenuGroup>
						</>
					) }
				</DropdownMenu>
			</ButtonGroup>
		</div>
	);
};
