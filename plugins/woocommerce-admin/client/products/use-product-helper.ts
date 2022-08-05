/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
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
						recordEvent( 'product_preview', {
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

	const createProductWithStatus = useCallback(
		async (
			product: Omit< Product, ReadOnlyProperties >,
			status: ProductStatus,
			skipNotice = false,
			skipRedirect = false
		) => {
			createProduct( {
				...product,
				status,
			} ).then(
				( newProduct ) => {
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
								actions: getNoticePreviewActions(
									newProduct.status,
									newProduct.permalink
								),
							}
						);
					}
				},
				() => {
					createNotice(
						'error',
						status === 'publish'
							? __( 'Failed to publish product.', 'woocommerce' )
							: __( 'Failed to create product.', 'woocommerce' )
					);
				}
			);
		},
		[]
	);

	const updateProductWithStatus = async (
		product: Product,
		status: ProductStatus,
		skipNotice = false
	) => {
		return updateProduct( product.id, {
			...product,
			status,
		} ).then(
			( updatedProduct ) => {
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
							actions: getNoticePreviewActions(
								updatedProduct.status,
								updatedProduct.permalink
							),
						}
					);
				}
			},
			() => {
				createNotice(
					'error',
					__( 'Failed to update product.', 'woocommerce' )
				);
			}
		);
	};

	const copyProductWithStatus = async (
		product: Product,
		status: ProductStatus = 'draft'
	) => {
		return createProductWithStatus(
			removeReadonlyProperties( {
				...product,
				name: ( product.name || 'AUTO-DRAFT' ) + ' - Copy',
			} ),
			status
		);
	};

	const deleteProductAndRedirect = (
		id: number,
		redirectUrl = 'edit.php?post_type=product'
	) => {
		deleteProduct( id ).then( () => {
			createNotice(
				'success',
				__( '🎉 Successfully moved product to Trash.', 'woocommerce' )
			);
			navigateTo( {
				url: redirectUrl,
			} );
		} );
	};

	return {
		createProductWithStatus,
		updateProductWithStatus,
		copyProductWithStatus,
		deleteProductAndRedirect,
	};
}
