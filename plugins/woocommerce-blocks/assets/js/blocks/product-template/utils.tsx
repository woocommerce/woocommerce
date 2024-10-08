/**
 * External dependencies
 */
import { resolveSelect, useSelect } from '@wordpress/data';
import { useState, useEffect, useMemo } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';

export const enum LocationType {
	Product = 'product',
	Archive = 'archive',
	Cart = 'cart',
	Order = 'order',
	Site = 'site',
}
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

interface WooCommerceBaseLocation {
	type: LocationType;
	sourceData?: object | undefined;
}

interface ProductLocation extends WooCommerceBaseLocation {
	type: LocationType.Product;
	sourceData?:
		| {
				productId: number;
		  }
		| undefined;
}

interface ArchiveLocation extends WooCommerceBaseLocation {
	type: LocationType.Archive;
	sourceData?:
		| {
				taxonomy: string;
				termId: number;
		  }
		| undefined;
}

interface CartLocation extends WooCommerceBaseLocation {
	type: LocationType.Cart;
	sourceData?:
		| {
				productIds: number[];
		  }
		| undefined;
}

interface OrderLocation extends WooCommerceBaseLocation {
	type: LocationType.Order;
	sourceData?:
		| {
				orderId: number;
		  }
		| undefined;
}

interface SiteLocation extends WooCommerceBaseLocation {
	type: LocationType.Site;
	sourceData?: object | undefined;
}

export type WooCommerceBlockLocation =
	| ProductLocation
	| ArchiveLocation
	| CartLocation
	| OrderLocation
	| SiteLocation;

const createLocationObject = ( type: LocationType, sourceData: object = {} ) =>
	( {
		type,
		sourceData,
	} as WooCommerceBlockLocation );

type ContextProperties = {
	templateSlug: string;
	postId?: string;
};

export const useGetLocation = < T, >(
	context: Context< T & ContextProperties >,
	clientId: string
): WooCommerceBlockLocation => {
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
			const isInBlocks = ( parentBlockNames: string[] ) =>
				getBlockParentsByBlockName( clientId, parentBlockNames )
					.length > 0;

			return {
				isInSingleProductBlock: isInBlocks( [
					'woocommerce/single-product',
				] ),
				isInSomeCartCheckoutBlock: isInBlocks( [
					'woocommerce/cart',
					'woocommerce/checkout',
					'woocommerce/mini-cart-contents',
				] ),
			};
		},
		[ clientId ]
	);

	/**
	 * Case 1.1: BLOCK LEVEL: SPECIFIC PRODUCT
	 * Single Product block - take product ID from context
	 */

	if ( isInSingleProductBlock ) {
		return createLocationObject( LocationType.Product, {
			productId: postId,
		} );
	}

	/**
	 * Case 1.2: BLOCK LEVEL: GENERIC CART
	 * Cart, Checkout or Mini Cart blocks - block scope is more important than template
	 */

	if ( isInSomeCartCheckoutBlock ) {
		return createLocationObject( LocationType.Cart );
	}

	/**
	 * Case 2.1: TEMPLATES: SPECIFIC PRODUCT
	 * Specific Single Product template - take product ID from taxononmy
	 */

	if ( isInSpecificProductTemplate ) {
		return createLocationObject( LocationType.Product, { productId } );
	}

	const isInGenericTemplate = prepareIsInGenericTemplate( templateSlug );

	/**
	 * Case 2.2: TEMPLATES: GENERIC PRODUCT
	 * Generic Single Product template
	 */

	const isInSingleProductTemplate = isInGenericTemplate(
		templateSlugs.singleProduct
	);

	if ( isInSingleProductTemplate ) {
		return createLocationObject( LocationType.Product, {
			productId: null,
		} );
	}

	/**
	 * Case 2.3: TEMPLATES: SPECIFIC TAXONOMY
	 * Specific Category template - take category ID from
	 */

	if ( isInSpecificCategoryTemplate ) {
		return createLocationObject( LocationType.Archive, {
			taxonomy: 'product_cat',
			termId: categoryId,
		} );
	}

	/**
	 * Case 2.4: TEMPLATES: SPECIFIC TAXONOMY
	 * Specific Tag template
	 */

	if ( isInSpecificTagTemplate ) {
		return createLocationObject( LocationType.Archive, {
			taxonomy: 'product_tag',
			termId: tagId,
		} );
	}

	/**
	 * Case 2.5: TEMPLATES: GENERIC TAXONOMY
	 * Generic Taxonomy template
	 */

	const isInProductsByCategoryTemplate = isInGenericTemplate(
		templateSlugs.productCategory
	);

	if ( isInProductsByCategoryTemplate ) {
		return createLocationObject( LocationType.Archive, {
			taxonomy: 'product_cat',
			termId: null,
		} );
	}

	const isInProductsByTagTemplate = isInGenericTemplate(
		templateSlugs.productTag
	);

	if ( isInProductsByTagTemplate ) {
		return createLocationObject( LocationType.Archive, {
			taxonomy: 'product_tag',
			termId: null,
		} );
	}

	const isInProductsByAttributeTemplate = isInGenericTemplate(
		templateSlugs.productAttribute
	);

	if ( isInProductsByAttributeTemplate ) {
		return createLocationObject( LocationType.Archive, {
			taxonomy: null,
			termId: null,
		} );
	}

	/**
	 * Case 2.6: TEMPLATES: GENERIC CART
	 * Cart/Checkout templates
	 */

	const isInCartCheckoutTemplate =
		templateSlug === templateSlugs.cart ||
		templateSlug === templateSlugs.checkout;

	if ( isInCartCheckoutTemplate ) {
		return createLocationObject( LocationType.Cart );
	}

	/**
	 * Case 2.7: TEMPLATES: GENERIC ORDER
	 * Order Confirmation template
	 */

	const isInOrderTemplate = isInGenericTemplate(
		templateSlugs.orderConfirmation
	);

	if ( isInOrderTemplate ) {
		return createLocationObject( LocationType.Order );
	}

	/**
	 * Case 3: GENERIC
	 * All other cases
	 */

	return createLocationObject( LocationType.Site );
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

export const parseTemplateSlug = ( rawTemplateSlug = '' ) => {
	const categoryPrefix = 'category-';
	const productCategoryPrefix = 'taxonomy-product_cat-';
	const productTagPrefix = 'taxonomy-product_tag-';

	if ( rawTemplateSlug.startsWith( categoryPrefix ) ) {
		return {
			taxonomy: 'category',
			slug: rawTemplateSlug.replace( categoryPrefix, '' ),
		};
	}

	if ( rawTemplateSlug.startsWith( productCategoryPrefix ) ) {
		return {
			taxonomy: 'product_cat',
			slug: rawTemplateSlug.replace( productCategoryPrefix, '' ),
		};
	}

	if ( rawTemplateSlug.startsWith( productTagPrefix ) ) {
		return {
			taxonomy: 'product_tag',
			slug: rawTemplateSlug.replace( productTagPrefix, '' ),
		};
	}

	return { taxonomy: '', slug: '' };
};
