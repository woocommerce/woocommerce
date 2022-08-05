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
import { useDispatch } from '@wordpress/data';
import { chevronDown } from '@wordpress/icons';
import { useFormContext } from '@woocommerce/components';
import {
	Product,
	ProductsStoreActions,
	ProductStatus,
	PRODUCTS_STORE_NAME,
	ReadOnlyProperties,
	productReadOnlyProperties,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { navigateTo } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './product-form-actions.scss';

function removeReadonlyProperties(
	product: Product
): Omit< Product, ReadOnlyProperties > {
	productReadOnlyProperties.forEach( ( key ) => delete product[ key ] );
	return product;
}

export const ProductFormActions: React.FC = () => {
	const { createProduct, updateProduct, deleteProduct } = useDispatch(
		PRODUCTS_STORE_NAME
	) as ProductsStoreActions;
	const { createNotice } = useDispatch( 'core/notices' );
	const { isDirty, values } = useFormContext< Product >();

	const createProductWithStatus = async (
		product: Omit< Product, ReadOnlyProperties >,
		status: ProductStatus,
		skipNotice = false,
		skipRedirect = false
	) => {
		return createProduct( {
			...product,
			status,
		} ).then( ( newProduct ) => {
			if ( ! skipRedirect ) {
				navigateTo( {
					url:
						'admin.php?page=wc-admin&path=/product/' +
						newProduct.id,
				} );
			}
			if ( ! skipNotice ) {
				createNotice(
					'success',
					newProduct.status === 'publish'
						? __(
								'🎉 Product published. View in store',
								'woocommerce'
						  )
						: __(
								'🎉 Product successfully created.',
								'woocommerce'
						  ),
					{
						actions:
							newProduct.status === 'publish' &&
							newProduct.permalink
								? [
										{
											label: __(
												'View in store',
												'woocommerce'
											),
											onClick: () => {
												recordEvent(
													'product_preview',
													{
														new_product_page: true,
													}
												);
												window.open(
													newProduct.permalink,
													'_blank'
												);
											},
										},
								  ]
								: [],
					}
				);
			}
		} );
	};

	const updateProductWithStatus = async (
		product: Omit< Product, ReadOnlyProperties >,
		status: ProductStatus,
		skipNotice = false
	) => {
		return updateProduct( values.id, {
			...product,
			status,
		} ).then( ( updatedProduct ) => {
			if ( ! skipNotice ) {
				createNotice(
					'success',
					updatedProduct.status === 'publish'
						? __(
								'🎉 Product successfully updated.',
								'woocommerce'
						  )
						: __(
								'🎉 Product successfully updated.',
								'woocommerce'
						  ),
					{
						actions:
							updatedProduct.status === 'publish' &&
							updatedProduct.permalink
								? [
										{
											label: __(
												'View in store',
												'woocommerce'
											),
											onClick: () => {
												recordEvent(
													'product_preview',
													{
														new_product_page: true,
													}
												);
												window.open(
													updatedProduct.permalink,
													'_blank'
												);
											},
										},
								  ]
								: [],
					}
				);
			}
		} );
	};

	const onSaveDraft = () => {
		recordEvent( 'product_edit', {
			new_product_page: true,
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
		} );
		if ( ! values.id ) {
			createProductWithStatus( values, 'publish' );
		} else {
			updateProductWithStatus( values, 'publish' );
		}
	};

	const onPublishAndDuplicate = async () => {
		recordEvent( 'product_update_and_duplicate', {
			new_product_page: true,
		} );
		if ( values.id ) {
			await updateProductWithStatus( values, 'publish' );
		} else {
			await createProductWithStatus( values, 'publish', false, true );
		}
		await createProductWithStatus(
			removeReadonlyProperties( {
				...values,
				name: ( values.name || 'AUTO-DRAFT' ) + ' - Copy',
			} ),
			'draft'
		);
	};

	const onCopyToNewDraft = async () => {
		recordEvent( 'product_copy', {
			new_product_page: true,
		} );
		if ( values.id ) {
			await updateProductWithStatus( values, values.status || 'draft' );
		}
		await createProductWithStatus(
			removeReadonlyProperties( {
				...values,
				name: ( values.name || 'AUTO-DRAFT' ) + ' - Copy',
			} ),
			values.status || 'draft'
		);
	};

	const onTrash = () => {
		recordEvent( 'product_delete', {
			new_product_page: true,
		} );
		if ( values.id ) {
			deleteProduct( values.id ).then( () => {
				createNotice(
					'success',
					__(
						'🎉 Successfully moved product to Trash.',
						'woocommerce'
					)
				);
				navigateTo( {
					url: 'edit.php?post_type=product',
				} );
			} );
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
					recordEvent( 'product_preview', {
						new_product_page: true,
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
									{ __(
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
								<MenuItem onClick={ onTrash } isDestructive>
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
