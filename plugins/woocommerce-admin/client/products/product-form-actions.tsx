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
	const { isDirty, values } = useFormContext< Product >();

	const getProductDataForTracks = () => {
		return {
			product_id: values.id,
			product_type: values.type,
			is_downloadable: values.downloadable,
			is_virtual: values.virtual,
			manage_stock: values.manage_stock,
		};
	};

	const onSaveDraft = () => {
		recordEvent( 'product_edit', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( ! values.id ) {
			createProductWithStatus( values, 'draft' );
		} else {
			updateProductWithStatus( values, 'draft' );
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
			updateProductWithStatus( values, 'publish' );
		}
	};

	const onPublishAndDuplicate = async () => {
		recordEvent( 'product_publish_and_copy', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( values.id ) {
			await updateProductWithStatus( values, 'publish' );
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
			await updateProductWithStatus( values, values.status || 'draft' );
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
			{ values.status !== 'publish' ? (
				<Button
					onClick={ onSaveDraft }
					disabled={
						( ! isDirty && !! values.id ) ||
						isUpdatingDraft ||
						isUpdatingPublished ||
						isDeleting
					}
				>
					{ ! isDirty && values.id && <Icon icon={ check } /> }
					{ isUpdatingDraft ? __( 'Saving', 'woocommerce' ) : null }
					{ ( isDirty || ! values.id ) && ! isUpdatingDraft
						? __( 'Save draft', 'woocommerce' )
						: null }
					{ ! isDirty && values.id && ! isUpdatingDraft
						? __( 'Saved', 'woocommerce' )
						: null }
				</Button>
			) : null }
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
