/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { useCallback, useState } from '@wordpress/element';
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

function removeReadonlyProperties(
	product: Product
): Omit< Product, ReadOnlyProperties > {
	productReadOnlyProperties.forEach( ( key ) => delete product[ key ] );
	return product;
}

function getNoticePreviewActions( status: ProductStatus, permalink: string ) {
	return status === 'publish' && permalink
		? [
				{
					label: __( 'View in store', 'woocommerce' ),
					onClick: () => {
						recordEvent( 'product_preview_changes', {
							new_product_page: true,
						} );
						window.open( permalink, '_blank' );
					},
				},
		  ]
		: [];
}

export function useProductHelper() {
	const { createProduct, updateProduct, deleteProduct } = useDispatch(
		PRODUCTS_STORE_NAME
	) as ProductsStoreActions;
	const { createNotice } = useDispatch( 'core/notices' );
	const [ isDeleting, setIsDeleting ] = useState( false );
	const [ updating, setUpdating ] = useState( {
		draft: false,
		publish: false,
	} );

	/**
	 * Create product with status.
	 *
	 * @param {Product} product the product to be created.
	 * @param {string}  status the product status.
	 * @param {boolean} skipNotice if the notice should be skipped (default: false).
	 * @param {boolean} skipRedirect if the user should skip the redirection to the new product page (default: false).
	 * @return {Promise<Product>} Returns a promise with the created product.
	 */
	const createProductWithStatus = useCallback(
		async (
			product: Omit< Product, ReadOnlyProperties >,
			status: ProductStatus,
			skipNotice = false,
			skipRedirect = false
		) => {
			setUpdating( {
				...updating,
				[ status ]: true,
			} );
			createProduct( {
				...product,
				status,
			} ).then(
				( newProduct ) => {
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
								actions: getNoticePreviewActions(
									newProduct.status,
									newProduct.permalink
								),
							}
						);
					}
					setUpdating( {
						...updating,
						[ status ]: false,
					} );
					if ( ! skipRedirect ) {
						navigateTo( {
							url:
								'admin.php?page=wc-admin&path=/product/' +
								newProduct.id,
						} );
					}
				},
				() => {
					if ( ! skipNotice ) {
						createNotice(
							'error',
							status === 'publish'
								? __(
										'Failed to publish product.',
										'woocommerce'
								  )
								: __(
										'Failed to create product.',
										'woocommerce'
								  )
						);
					}
					setUpdating( {
						...updating,
						[ status ]: false,
					} );
				}
			);
		},
		[ updating ]
	);

	/**
	 * Update product with status.
	 *
	 * @param {number} productId the product id to be updated.
	 * @param {Product} product the product to be updated.
	 * @param {string}  status the product status.
	 * @param {boolean} skipNotice if the notice should be skipped (default: false).
	 * @return {Promise<Product>} Returns a promise with the updated product.
	 */
	const updateProductWithStatus = useCallback(
		async (
			productId: number,
			product: Partial< Product >,
			status: ProductStatus,
			skipNotice = false
		): Promise< Product > => {
			setUpdating( {
				...updating,
				[ status ]: true,
			} );
			return updateProduct( productId, {
				...product,
				status,
			} ).then(
				( updatedProduct ) => {
					if ( ! skipNotice ) {
						createNotice(
							'success',
							product.status === 'draft' &&
								updatedProduct.status === 'publish'
								? __(
										'🎉 Product published. View in store.',
										'woocommerce'
								  )
								: __(
										'🎉 Product successfully updated.',
										'woocommerce'
								  ),
							{
								actions: getNoticePreviewActions(
									updatedProduct.status,
									updatedProduct.permalink
								),
							}
						);
					}
					setUpdating( {
						...updating,
						[ status ]: false,
					} );
					return updatedProduct;
				},
				( error ) => {
					if ( ! skipNotice ) {
						createNotice(
							'error',
							__( 'Failed to update product.', 'woocommerce' )
						);
					}
					setUpdating( {
						...updating,
						[ status ]: false,
					} );
					return error;
				}
			);
		},
		[ updating ]
	);

	/**
	 * Creates a copy of the given product with the given status.
	 *
	 * @param {Product} product the product to be copied.
	 * @param {string}  status the product status.
	 * @return {Promise<Product>} promise with the newly created and copied product.
	 */
	const copyProductWithStatus = useCallback(
		async ( product: Product, status: ProductStatus = 'draft' ) => {
			return createProductWithStatus(
				removeReadonlyProperties( {
					...product,
					name: ( product.name || 'AUTO-DRAFT' ) + ' - Copy',
				} ),
				status
			);
		},
		[]
	);

	/**
	 * Deletes a product by given id and redirects to the product list page.
	 *
	 * @param {number} id the product id to be deleted.
	 * @param {string} redirectUrl the redirection url, defaults to product list ('edit.php?post_type=product').
	 * @return {Promise<Product>} promise with the deleted product.
	 */
	const deleteProductAndRedirect = useCallback(
		( id: number, redirectUrl = 'edit.php?post_type=product' ) => {
			setIsDeleting( true );
			return deleteProduct( id ).then( () => {
				createNotice(
					'success',
					__(
						'🎉 Successfully moved product to Trash.',
						'woocommerce'
					)
				);
				navigateTo( {
					url: redirectUrl,
				} );
				setIsDeleting( false );
			} );
		},
		[]
	);

	return {
		createProductWithStatus,
		updateProductWithStatus,
		copyProductWithStatus,
		deleteProductAndRedirect,
		isUpdatingDraft: updating.draft,
		isUpdatingPublished: updating.publish,
		isDeleting,
	};
}
