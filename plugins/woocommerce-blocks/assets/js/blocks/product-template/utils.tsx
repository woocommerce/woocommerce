/**
 * External dependencies
 */
import { resolveSelect, useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { isNumber } from '@woocommerce/types';

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

const parseResponse = ( resp?: Record< 'id', number >[] ): number | null =>
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
	const entityId = parseResponse( response );
	stateSetter( entityId );
};

const createGetEntitySlug =
	( templateSlug: string ) =>
	( entitySlug: string ): string =>
		templateSlug.replace( `${ entitySlug }-`, '' );

const createLocationObject = ( type: LocationType, sourceData = {} ) => ( {
	type,
	sourceData,
} );

export const useGetLocation = < T, >(
	context: Context< T >,
	clientId: string
) => {
	const templateSlug = context.templateSlug || '';
	const postId = context.postId || null;

	const getEntitySlug = createGetEntitySlug( templateSlug || '' );

	const { isInSingleProductBlock, isInMiniCartBlock } = useSelect(
		( select ) => {
			const isInSingleProductBlock =
				select( blockEditorStore ).getBlockParentsByBlockName(
					clientId,
					'woocommerce/single-product'
				).length > 0;

			const isInMiniCartBlock =
				select( blockEditorStore ).getBlockParentsByBlockName(
					clientId,
					'woocommerce/mini-cart-contents'
				).length > 0;
			return { isInSingleProductBlock, isInMiniCartBlock };
		}
	);

	const isInSpecificProductTemplate =
		templateSlug.includes( 'single-product' ) &&
		templateSlug !== 'single-product';
	const isInSpecificCategoryTemplate =
		templateSlug.includes( 'taxonomy-product_cat' ) &&
		templateSlug !== 'taxonomy-product_cat';
	const isInSpecificTagTemplate =
		templateSlug.includes( 'taxonomy-product_tag' ) &&
		templateSlug !== 'taxonomy-product_tag';

	const isInSingleProductTemplate = templateSlug === 'single-product';
	const isInProductsByCategoryTemplate =
		templateSlug === 'taxonomy-product_cat';
	const isInProductsByTagTemplate = templateSlug === 'taxonomy-product_tag';
	const isInProductsByAttributeTemplate =
		templateSlug === 'taxonomy-product_attribute';
	const isInOrderTemplate = templateSlug === 'order-confirmation';

	const isInCartContext =
		templateSlug === 'page-cart' ||
		templateSlug === 'page-checkout' ||
		isInMiniCartBlock;

	const [ productId, setProductId ] = useState< number | null >( null );
	const [ catId, setCatId ] = useState< number | null >( null );
	const [ tagId, setTagId ] = useState< number | null >( null );

	useEffect( () => {
		if ( isInSpecificProductTemplate ) {
			const slug = getEntitySlug( 'single-product' );
			setEntityId( 'postType', 'product', slug, setProductId );
		}

		if ( isInSpecificCategoryTemplate ) {
			const slug = getEntitySlug( 'taxonomy-product_cat' );
			setEntityId( 'taxonomy', 'product_cat', slug, setCatId );
		}

		if ( isInSpecificTagTemplate ) {
			const slug = getEntitySlug( 'taxonomy-product_tag' );
			setEntityId( 'taxonomy', 'product_tag', slug, setTagId );
		}
	} );

	/**
	 * Case 1.1: SPECIFIC PRODUCT
	 * Single Product block - take product ID from context
	 */

	if ( isInSingleProductBlock ) {
		return createLocationObject( 'product', { productId: postId } );
	}

	/**
	 * Case 1.2: SPECIFIC PRODUCT
	 * Specific Single Product template - take product ID from taxononmy
	 */

	if ( isInSpecificProductTemplate ) {
		return createLocationObject( 'product', { productId } );
	}

	/**
	 * Case 1.3: GENERIC PRODUCT
	 * Generic Single Product template
	 */

	if ( isInSingleProductTemplate ) {
		return createLocationObject( 'product', { postId: null } );
	}

	/**
	 * Case 2.1: SPECIFIC TAXONOMY
	 * Specific Category template - take category ID from
	 */

	if ( isInSpecificCategoryTemplate ) {
		return createLocationObject( 'archive', {
			taxonomy: 'cat',
			termId: catId,
		} );
	}

	/**
	 * Case 2.2: SPECIFIC TAXONOMY
	 * Specific Tag template
	 */

	if ( isInSpecificTagTemplate ) {
		return createLocationObject( 'archive', {
			taxonomy: 'tag',
			termId: tagId,
		} );
	}

	/**
	 * Case 2.3: GENERIC TAXONOMY
	 * Generic Taxonomy template
	 */

	if ( isInProductsByCategoryTemplate ) {
		return createLocationObject( 'archive', {
			taxonomy: 'cat',
			termId: null,
		} );
	}

	if ( isInProductsByTagTemplate ) {
		return createLocationObject( 'archive', {
			taxonomy: 'tag',
			termId: null,
		} );
	}

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

	if ( isInCartContext ) {
		return createLocationObject( 'cart' );
	}

	/**
	 * Case 4: GENERIC ORDER
	 * Order Confirmation template
	 */

	if ( isInOrderTemplate ) {
		return createLocationObject( 'order' );
	}

	/**
	 * Case 5: GENERIC
	 * All other cases
	 */

	return createLocationObject( 'generic' );
};
