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
import { registerPlugin } from '@wordpress/plugins';
import { useFormContext2 } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { navigateTo } from '@woocommerce/navigation';
import { useSelect } from '@wordpress/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store } from '@wordpress/viewport';

/**
 * Internal dependencies
 */
import { preventLeavingProductForm } from './utils/prevent-leaving-product-form';
import usePreventLeavingPage from '~/hooks/usePreventLeavingPage';
import { WooHeaderItem } from '~/header/utils';
import { useProductHelper } from './use-product-helper';
import './product-form-actions.scss';
import { useProductMVPCESFooter } from '~/customer-effort-score-tracks/use-product-mvp-ces-footer';
import { useCustomerEffortScoreExitPageTracker } from '~/customer-effort-score-tracks/use-customer-effort-score-exit-page-tracker';

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

	const { onPublish: triggerPublishCES, onSaveDraft: triggerDraftCES } =
		useProductMVPCESFooter();
	const { formState, getValues, reset } = useFormContext2< Product >();
	const isValidForm = formState.isValid;
	const values = getValues() as Product;

	usePreventLeavingPage( formState.isDirty, preventLeavingProductForm );

	useCustomerEffortScoreExitPageTracker(
		! values.id ? 'new_product' : 'editing_new_product',
		formState.isDirty
	);

	const { isSmallViewport } = useSelect( ( select ) => {
		return {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			//@ts-ignore Types don't appear to be working correctly on this package.
			isSmallViewport: select( store ).isViewportMatch( '< medium' ),
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

	const onSaveDraft = async () => {
		recordEvent( 'product_edit', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( ! values.id ) {
			const product = await createProductWithStatus( values, 'draft' );
			if ( product?.id ) {
				reset();
				navigateTo( {
					url: 'admin.php?page=wc-admin&path=/product/' + product.id,
				} );
			}
		} else {
			const product = await updateProductWithStatus(
				values.id,
				values,
				'draft'
			);
			if ( product && product.id ) {
				reset( product );
			}
		}
		await triggerDraftCES();
	};

	const onPublish = async () => {
		recordEvent( 'product_update', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( ! values.id ) {
			const product = await createProductWithStatus( values, 'publish' );
			if ( product?.id ) {
				reset();
				navigateTo( {
					url: 'admin.php?page=wc-admin&path=/product/' + product.id,
				} );
			}
		} else {
			const product = await updateProductWithStatus(
				values.id,
				values,
				'publish'
			);
			if ( product && product.id ) {
				reset( product );
			}
		}
		await triggerPublishCES();
	};

	const onPublishAndDuplicate = async () => {
		recordEvent( 'product_publish_and_copy', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( values.id ) {
			await updateProductWithStatus( values.id, values, 'publish' );
		} else {
			await createProductWithStatus( values, 'publish', false );
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

	const onTrash = async () => {
		recordEvent( 'product_delete', {
			new_product_page: true,
			...getProductDataForTracks(),
		} );
		if ( values.id ) {
			const product = await deleteProductAndRedirect( values.id );
			if ( product?.id ) {
				reset( product );
				navigateTo( { url: 'edit.php?post_type=product' } );
			}
		}
	};

	const isPublished = values.id && values.status === 'publish';
	const SecondaryActionsComponent = isSmallViewport ? MenuItem : Button;
	const secondaryActions = (
		<>
			<SecondaryActionsComponent
				onClick={ onSaveDraft }
				disabled={
					! isValidForm ||
					( ! formState.isDirty &&
						!! values.id &&
						values.status !== 'publish' ) ||
					isUpdatingDraft ||
					isUpdatingPublished ||
					isDeleting
				}
			>
				{ ! formState.isDirty &&
					values.id &&
					values.status !== 'publish' && <Icon icon={ check } /> }
				{ isUpdatingDraft ? __( 'Saving', 'woocommerce' ) : null }
				{ ( formState.isDirty || ! values.id ) &&
				! isUpdatingDraft &&
				values.status !== 'publish'
					? __( 'Save draft', 'woocommerce' )
					: null }
				{ values.status === 'publish' && ! isUpdatingDraft
					? __( 'Switch to draft', 'woocommerce' )
					: null }
				{ ! formState.isDirty &&
				values.id &&
				! isUpdatingDraft &&
				values.status !== 'publish'
					? __( 'Saved', 'woocommerce' )
					: null }
			</SecondaryActionsComponent>
			<SecondaryActionsComponent
				onClick={ () =>
					recordEvent( 'product_preview_changes', {
						new_product_page: true,
						...getProductDataForTracks(),
					} )
				}
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				//@ts-ignore The `href` prop works for both Buttons and MenuItem's.
				href={ values.permalink + '?preview=true' }
				disabled={ ! isValidForm || ! values.permalink }
				target="_blank"
			>
				{ __( 'Preview', 'woocommerce' ) }
			</SecondaryActionsComponent>
		</>
	);

	return (
		<WooHeaderItem>
			{ () => (
				<div className="woocommerce-product-form-actions">
					{ ! isSmallViewport && secondaryActions }
					<ButtonGroup className="woocommerce-product-form-actions__publish-button-group">
						<Button
							onClick={ onPublish }
							variant="primary"
							isBusy={ isUpdatingPublished }
							disabled={
								! isValidForm ||
								( ! formState.isDirty && !! isPublished ) ||
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
							toggleProps={ {
								variant: 'primary',
								disabled: ! values.id && ! isValidForm,
							} }
						>
							{ () => (
								<>
									<MenuGroup>
										{ isSmallViewport && secondaryActions }
										<MenuItem
											onClick={ onPublishAndDuplicate }
											disabled={ ! isValidForm }
										>
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
										<MenuItem
											onClick={ onCopyToNewDraft }
											disabled={ ! isValidForm }
										>
											{ __(
												'Copy to a new draft',
												'woocommerce'
											) }
										</MenuItem>
										{ values.id && (
											<MenuItem
												onClick={ onTrash }
												isDestructive
											>
												{ __(
													'Move to trash',
													'woocommerce'
												) }
											</MenuItem>
										) }
									</MenuGroup>
								</>
							) }
						</DropdownMenu>
					</ButtonGroup>
				</div>
			) }
		</WooHeaderItem>
	);
};

registerPlugin( 'action-buttons-header-item', {
	render: ProductFormActions,
	icon: 'admin-generic',
} );
