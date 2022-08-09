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
import { useSelect } from '@wordpress/data';
import { chevronDown } from '@wordpress/icons';
import { useFormContext } from '@woocommerce/components';
import {
	Product,
	PRODUCTS_STORE_NAME,
	WCDataSelector,
} from '@woocommerce/data';
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
	} = useProductHelper();
	const { isDirty, values } = useFormContext< Product >();
	const { isPendingAction } = useSelect( ( select: WCDataSelector ) => {
		const { isPending } = select( PRODUCTS_STORE_NAME );
		return {
			isPendingAction:
				isPending( 'createProduct' ) ||
				isPending( 'deleteProduct', values.id ) ||
				isPending( 'updateProduct', values.id ),
		};
	} );

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

	const onPublish = () => {
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
					disabled={ ! isDirty && !! values.id }
				>
					{ __( 'Save draft', 'woocommerce' ) }
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
					isBusy={ isPendingAction }
					disabled={ ! isDirty && !! isPublished }
				>
					{ isPublished
						? __( 'Update', 'woocommerce' )
						: __( 'Publish', 'woocommerce' ) }
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
