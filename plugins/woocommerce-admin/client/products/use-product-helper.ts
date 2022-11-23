/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { useCallback, useContext, useState } from '@wordpress/element';
import * as WooNumber from '@woocommerce/number';
import {
	Product,
	ProductsStoreActions,
	ProductStatus,
	PRODUCTS_STORE_NAME,
	ReadOnlyProperties,
	productReadOnlyProperties,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CurrencyContext } from '../lib/currency-context';
import {
	NUMBERS_AND_DECIMAL_SEPARATOR,
	ONLY_ONE_DECIMAL_SEPARATOR,
} from './constants';

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
	const context = useContext( CurrencyContext );

	/**
	 * Create product with status.
	 *
	 * @param {Product} product the product to be created.
	 * @param {string}  status the product status.
	 * @param {boolean} skipNotice if the notice should be skipped (default: false).
	 * @return {Promise<Product>} Returns a promise with the created product.
	 */
	const createProductWithStatus = useCallback(
		async (
			product: Omit< Product, ReadOnlyProperties >,
			status: ProductStatus,
			skipNotice = false
		) => {
			setUpdating( {
				...updating,
				[ status ]: true,
			} );
			return createProduct( {
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
					return newProduct;
				},
				( error ) => {
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
					return error;
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
	 * @return {Promise<Product>} promise with the deleted product.
	 */
	const deleteProductAndRedirect = useCallback( async ( id: number ) => {
		setIsDeleting( true );
		return deleteProduct( id ).then(
			( product ) => {
				createNotice(
					'success',
					__(
						'🎉 Successfully moved product to Trash.',
						'woocommerce'
					)
				);
				setIsDeleting( false );
				return product;
			},
			( error ) => {
				createNotice(
					'error',
					__( 'Failed to move product to Trash.', 'woocommerce' )
				);
				setIsDeleting( false );
				return error;
			}
		);
	}, [] );

	/**
	 * Sanitizes a price.
	 *
	 * @param {string} price the price that will be sanitized.
	 * @return {string} sanitized price.
	 */
	const sanitizePrice = useCallback(
		( price: string ) => {
			const { getCurrencyConfig } = context;
			const { decimalSeparator } = getCurrencyConfig();
			// Build regex to strip out everything except digits, decimal point and minus sign.
			const regex = new RegExp(
				NUMBERS_AND_DECIMAL_SEPARATOR.replace( '%s', decimalSeparator ),
				'g'
			);
			const decimalRegex = new RegExp(
				ONLY_ONE_DECIMAL_SEPARATOR.replaceAll( '%s', decimalSeparator ),
				'g'
			);
			const cleanValue = price
				.replace( regex, '' )
				.replace( decimalRegex, '' )
				.replace( decimalSeparator, '.' );
			return cleanValue;
		},
		[ context ]
	);

	/**
	 * Format a value using the Woo General Currency Settings.
	 *
	 * @param {string} value the value that will be formatted.
	 * @return {string} the formatted number.
	 */
	const formatNumber = useCallback(
		( value: string ): string => {
			const { getCurrencyConfig } = context;
			const { decimalSeparator, thousandSeparator } = getCurrencyConfig();

			return WooNumber.numberFormat(
				{ decimalSeparator, thousandSeparator },
				value
			);
		},
		[ context ]
	);

	/**
	 * Parse a value using the Woo General Currency Settings.
	 *
	 * @param {string} value the value that will be parsed.
	 * @return {string} the parsed number.
	 */
	const parseNumber = useCallback(
		( value: string ): string => {
			const { getCurrencyConfig } = context;
			const { decimalSeparator, thousandSeparator } = getCurrencyConfig();

			return WooNumber.parseNumber(
				{ decimalSeparator, thousandSeparator },
				value
			);
		},
		[ context ]
	);

	return {
		createProductWithStatus,
		updateProductWithStatus,
		copyProductWithStatus,
		deleteProductAndRedirect,
		sanitizePrice,
		formatNumber,
		parseNumber,
		isUpdatingDraft: updating.draft,
		isUpdatingPublished: updating.publish,
		isDeleting,
	};
}
