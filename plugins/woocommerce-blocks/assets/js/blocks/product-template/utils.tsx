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

const parseResponse = ( resp?: Record< 'id', number >[] ): number | null =>
	resp && resp.length && resp[ 0 ]?.id ? resp[ 0 ].id : null;

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

	const { isChildOfSingleProductBlock, isChildOfMiniCartBlock } = useSelect(
		( select ) => {
			const isChildOfSingleProductBlock =
				select( blockEditorStore ).getBlockParentsByBlockName(
					clientId,
					'woocommerce/single-product'
				).length > 0;

			const isChildOfMiniCartBlock =
				select( blockEditorStore ).getBlockParentsByBlockName(
					clientId,
					'woocommerce/mini-cart-contents'
				).length > 0;
			return { isChildOfSingleProductBlock, isChildOfMiniCartBlock };
		}
	);

	const getEntitySlug = createGetEntitySlug( templateSlug || '' );

	const [ productId, setProductId ] = useState< number | null >( null );
	const [ catId, setCatId ] = useState< number | null >( null );
	const [ tagId, setTagId ] = useState< number | null >( null );

	/**
	 * Case 1.1: SPECIFIC PRODUCT
	 * Single Product block
	 */

	if ( isNumber( postId ) && isChildOfSingleProductBlock ) {
		return createLocationObject( 'product', { productId: postId } );
	}

	/**
	 * Case 1.2: SPECIFIC PRODUCT
	 * Specific Single Product template
	 */

	if (
		templateSlug.includes( 'single-product' ) &&
		templateSlug !== 'single-product'
	) {
		useEffect( () => {
			const slug = getEntitySlug( 'single-product' );
			const getProductId = async () => {
				const response = ( await resolveSelect(
					coreStore
				).getEntityRecords( 'postType', 'product', {
					_fields: [ 'id' ],
					slug,
				} ) ) as Record< 'id', number >[];
				const productId = parseResponse( response );
				setProductId( productId );
			};

			if ( slug ) {
				getProductId();
			}
		}, [ templateSlug ] );

		return createLocationObject( 'product', { productId } );
	}

	/**
	 * Case 1.3: GENERIC PRODUCT
	 * Generic Single Product template
	 */

	if ( templateSlug === 'single-product' ) {
		return createLocationObject( 'product', { postId: null } );
	}

	/**
	 * Case 2.1: SPECIFIC TAXONOMY
	 * Specific Category template
	 */

	if (
		templateSlug.includes( 'taxonomy-product_cat' ) &&
		templateSlug !== 'taxonomy-product_cat'
	) {
		useEffect( () => {
			const slug = getEntitySlug( 'taxonomy-product_cat' );
			const getCategoryId = async () => {
				const response = ( await resolveSelect(
					coreStore
				).getEntityRecords( 'taxonomy', 'product_cat', {
					_fields: [ 'id' ],
					slug,
				} ) ) as Record< 'id', number >[];
				const catId = parseResponse( response );
				setCatId( catId );
			};

			if ( slug ) {
				getCategoryId();
			}
		}, [ templateSlug ] );
		return createLocationObject( 'archive', {
			taxonomy: 'cat',
			termId: catId,
		} );
	}

	/**
	 * Case 2.2: SPECIFIC TAXONOMY
	 * Specific Tag template
	 */

	if (
		templateSlug.includes( 'taxonomy-product_tag' ) &&
		templateSlug !== 'taxonomy-product_tag'
	) {
		useEffect( () => {
			const slug = getEntitySlug( 'taxonomy-product_tag' );
			const getTagId = async () => {
				const response = ( await resolveSelect(
					coreStore
				).getEntityRecords( 'taxonomy', 'product_tag', {
					_fields: [ 'id' ],
					slug,
				} ) ) as Record< 'id', number >[];
				const tagId = parseResponse( response );
				setTagId( tagId );
			};

			if ( slug ) {
				getTagId();
			}
		}, [ templateSlug ] );
		return createLocationObject( 'archive', {
			taxonomy: 'tag',
			termId: tagId,
		} );
	}

	/**
	 * Case 2.3: GENERIC TAXONOMY
	 * Generic Taxonomy template
	 */

	if ( templateSlug === 'taxonomy-product_cat' ) {
		return createLocationObject( 'archive', {
			taxonomy: 'cat',
			termId: null,
		} );
	}

	if ( templateSlug === 'taxonomy-product_tag' ) {
		return createLocationObject( 'archive', {
			taxonomy: 'tag',
			termId: null,
		} );
	}

	if ( templateSlug === 'taxonomy-product_attribute' ) {
		return createLocationObject( 'archive', {
			taxonomy: null,
			termId: null,
		} );
	}

	/**
	 * Case 3: GENERIC CART
	 * Cart/Checkout templates or Mini Cart
	 */

	if (
		templateSlug === 'page-cart' ||
		templateSlug === 'page-checkout' ||
		isChildOfMiniCartBlock
	) {
		return createLocationObject( 'cart' );
	}

	/**
	 * Case 4: GENERIC ORDER
	 * Order Confirmation template
	 */

	if ( templateSlug === 'order-confirmation' ) {
		return createLocationObject( 'order' );
	}

	/**
	 * Case 5: GENERIC
	 * All other cases
	 */

	return createLocationObject( 'generic' );
};
