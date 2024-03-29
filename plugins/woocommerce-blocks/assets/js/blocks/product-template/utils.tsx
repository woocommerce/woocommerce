/**
 * External dependencies
 */
import { resolveSelect, useSelect } from '@wordpress/data';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';

type LocationType = 'product' | 'archive' | 'cart' | 'order' | 'generic';
type Context< T > = T & {
	templateSlug?: string;
	postId?: number;
};
type SetEntityId = (
	kind: 'postType' | 'taxonomy',
	name: 'product' | 'product_cat' | 'product_tag',
	slug: string,
	stateSetter: ( entityId: number | null ) => void
) => void;

const templateSlugs = {
	singleProduct: 'single-product',
	productCategory: 'taxonomy-product_cat',
	productTag: 'taxonomy-product_tag',
	productAttribute: 'taxonomy-product_attribute',
	orderConfirmation: 'order-confirmation',
	cart: 'page-cart',
	checkout: 'page-checkout',
};

const getIdFromResponse = ( resp?: Record< 'id', number >[] ): number | null =>
	resp && resp.length && resp[ 0 ]?.id ? resp[ 0 ].id : null;

const setEntityId: SetEntityId = async ( kind, name, slug, stateSetter ) => {
	const response = ( await resolveSelect( coreStore ).getEntityRecords(
		kind,
		name,
		{
			_fields: [ 'id' ],
			slug,
		}
	) ) as Record< 'id', number >[];
	const entityId = getIdFromResponse( response );
	stateSetter( entityId );
};

const prepareGetEntitySlug =
	( templateSlug: string ) =>
	( entitySlug: string ): string =>
		templateSlug.replace( `${ entitySlug }-`, '' );
const prepareIsInSpecificTemplate =
	( templateSlug: string ) =>
	( entitySlug: string ): boolean =>
		templateSlug.includes( entitySlug ) && templateSlug !== entitySlug;
const prepareIsInGenericTemplate =
	( templateSlug: string ) =>
	( entitySlug: string ): boolean =>
		templateSlug === entitySlug;

const createLocationObject = ( type: LocationType, sourceData = {} ) => ( {
	type,
	sourceData,
} );

type ContextProperties = {
	templateSlug: string;
	postId?: string;
};

export const useGetLocation = < T, >(
	context: Context< T & ContextProperties >,
	clientId: string
) => {
	const templateSlug = context.templateSlug || '';
	const postId = context.postId || null;

	const getEntitySlug = prepareGetEntitySlug( templateSlug );
	const isInSpecificTemplate = prepareIsInSpecificTemplate( templateSlug );

	// Detect Specific Templates
	const isInSpecificProductTemplate = isInSpecificTemplate(
		templateSlugs.singleProduct
	);
	const isInSpecificCategoryTemplate = isInSpecificTemplate(
		templateSlugs.productCategory
	);
	const isInSpecificTagTemplate = isInSpecificTemplate(
		templateSlugs.productTag
	);

	const [ productId, setProductId ] = useState< number | null >( null );
	const [ categoryId, setCategoryId ] = useState< number | null >( null );
	const [ tagId, setTagId ] = useState< number | null >( null );

	useEffect( () => {
		if ( isInSpecificProductTemplate ) {
			const slug = getEntitySlug( templateSlugs.singleProduct );
			setEntityId( 'postType', 'product', slug, setProductId );
		}

		if ( isInSpecificCategoryTemplate ) {
			const slug = getEntitySlug( templateSlugs.productCategory );
			setEntityId( 'taxonomy', 'product_cat', slug, setCategoryId );
		}

		if ( isInSpecificTagTemplate ) {
			const slug = getEntitySlug( templateSlugs.productTag );
			setEntityId( 'taxonomy', 'product_tag', slug, setTagId );
		}
	}, [
		isInSpecificProductTemplate,
		isInSpecificCategoryTemplate,
		isInSpecificTagTemplate,
		getEntitySlug,
	] );

	const { isInSingleProductBlock, isInSomeCartCheckoutBlock } = useSelect(
		( select ) => {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore No types for this selector exist yet
			const { getBlockParentsByBlockName } = select( blockEditorStore );
			const isInBlock = ( parentBlockName: string ) =>
				getBlockParentsByBlockName( clientId, parentBlockName ).length >
				0;

			const isInSingleProductBlock = isInBlock(
				'woocommerce/single-product'
			);
			const isInMiniCartBlock = isInBlock(
				'woocommerce/mini-cart-contents'
			);
			const isInCartBlock = isInBlock( 'woocommerce/cart' );
			const isInCheckoutBlock = isInBlock( 'woocommerce/checkout' );

			return {
				isInSingleProductBlock,
				isInSomeCartCheckoutBlock:
					isInMiniCartBlock || isInCartBlock || isInCheckoutBlock,
			};
		},
		[ clientId ]
	);

	/**
	 * Case 1.1: SPECIFIC PRODUCT
	 * Single Product block - take product ID from context
	 */

	if ( isInSingleProductBlock ) {
		return createLocationObject( 'product', { productId: postId } );
	}

	/**
	 * Case 1.2: BLOCK LEVEL: GENERIC CART
	 * Cart, Checkout or Mini Cart blocks - block scope is more important than template
	 */

	if ( isInSomeCartCheckoutBlock ) {
		return createLocationObject( 'cart' );
	}
	 * Specific Single Product template - take product ID from taxononmy
	 */

	if ( isInSpecificProductTemplate ) {
		return createLocationObject( 'product', { productId } );
	}

	const isInGenericTemplate = prepareIsInGenericTemplate( templateSlug );

	/**
	 * Case 1.3: GENERIC PRODUCT
	 * Generic Single Product template
	 */

	const isInSingleProductTemplate = isInGenericTemplate(
		templateSlugs.singleProduct
	);

	if ( isInSingleProductTemplate ) {
		return createLocationObject( 'product', { productId: null } );
	}

	/**
	 * Case 2.1: SPECIFIC TAXONOMY
	 * Specific Category template - take category ID from
	 */

	if ( isInSpecificCategoryTemplate ) {
		return createLocationObject( 'archive', {
			taxonomy: 'product_cat',
			termId: categoryId,
		} );
	}

	/**
	 * Case 2.2: SPECIFIC TAXONOMY
	 * Specific Tag template
	 */

	if ( isInSpecificTagTemplate ) {
		return createLocationObject( 'archive', {
			taxonomy: 'product_tag',
			termId: tagId,
		} );
	}

	/**
	 * Case 2.3: GENERIC TAXONOMY
	 * Generic Taxonomy template
	 */

	const isInProductsByCategoryTemplate = isInGenericTemplate(
		templateSlugs.productCategory
	);

	if ( isInProductsByCategoryTemplate ) {
		return createLocationObject( 'archive', {
			taxonomy: 'product_cat',
			termId: null,
		} );
	}

	const isInProductsByTagTemplate = isInGenericTemplate(
		templateSlugs.productTag
	);

	if ( isInProductsByTagTemplate ) {
		return createLocationObject( 'archive', {
			taxonomy: 'product_tag',
			termId: null,
		} );
	}

	const isInProductsByAttributeTemplate = isInGenericTemplate(
		templateSlugs.productAttribute
	);

	if ( isInProductsByAttributeTemplate ) {
		return createLocationObject( 'archive', {
			taxonomy: null,
			termId: null,
		} );
	}

	/**
	 * Case 3: GENERIC CART
	 * Cart/Checkout templates or Mini Cart
	 */

	const isInCartContext =
		templateSlug === templateSlugs.cart ||
		templateSlug === templateSlugs.checkout ||
		isInMiniCartBlock;

	if ( isInCartContext ) {
		return createLocationObject( 'cart' );
	}

	/**
	 * Case 4: GENERIC ORDER
	 * Order Confirmation template
	 */

	const isInOrderTemplate = isInGenericTemplate(
		templateSlugs.orderConfirmation
	);

	if ( isInOrderTemplate ) {
		return createLocationObject( 'order' );
	}

	/**
	 * Case 5: GENERIC
	 * All other cases
	 */

	return createLocationObject( 'generic' );
};

/**
 * In Product Collection block, queryContextIncludes attribute contains
 * list of attribute names that should be included in the query context.
 *
 * This hook returns the query context object based on the attribute names
 * provided in the queryContextIncludes array.
 *
 * Example:
 * {
 * 	clientID = 'd2c7e34f-70d6-417c-b582-f554a3a575f3',
 * 	queryContextIncludes = [ 'collection' ]
 * }
 *
 * The hook will return the following query context object:
 * {
 *  collection: 'woocommerce/product-collection/featured'
 * }
 *
 * @param args                      Arguments for the hook.
 * @param args.clientId             Client ID of the inner block.
 * @param args.queryContextIncludes Array of attribute names to be included in the query context.
 *
 * @return Query context object.
 */
export const useProductCollectionQueryContext = ( {
	clientId,
	queryContextIncludes,
}: {
	clientId: string;
	queryContextIncludes: string[];
} ) => {
	const productCollectionBlockAttributes = useSelect(
		( select ) => {
			const { getBlockParentsByBlockName, getBlockAttributes } =
				select( 'core/block-editor' );

			const parentBlocksClientIds = getBlockParentsByBlockName(
				clientId,
				'woocommerce/product-collection',
				true
			);

			if ( parentBlocksClientIds?.length ) {
				const closestParentClientId = parentBlocksClientIds[ 0 ];
				return getBlockAttributes( closestParentClientId );
			}

			return null;
		},
		[ clientId ]
	);

	return useMemo( () => {
		// If the product collection block is not found, return null.
		if ( ! productCollectionBlockAttributes ) {
			return null;
		}

		const queryContext: {
			[ key: string ]: unknown;
		} = {};

		if ( queryContextIncludes?.length ) {
			queryContextIncludes.forEach( ( attribute: string ) => {
				if ( productCollectionBlockAttributes?.[ attribute ] ) {
					queryContext[ attribute ] =
						productCollectionBlockAttributes[ attribute ];
				}
			} );
		}

		return queryContext;
	}, [ queryContextIncludes, productCollectionBlockAttributes ] );
};
