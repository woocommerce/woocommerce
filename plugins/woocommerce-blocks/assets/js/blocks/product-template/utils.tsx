/**
 * External dependencies
 */
import { resolveSelect, useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';
import { store as blockEditorStore } from '@wordpress/block-editor';
// import { store as editorStore } from '@wordpress/editor';
import { isNumber } from '@woocommerce/types';

import singleProductMetadata from '../single-product/block.json';

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

const createLocationObject = (
	type: LocationType,
	sourceData: { [ key: string ]: unknown }
) => ( {
	type,
	sourceData,
} );

export const useGetLocation = < T, >(
	context: Context< T >,
	clientId: string
) => {
	const templateSlug = context.templateSlug || '';
	const postId = context.postId || null;

	const isChildOfSingleProductBlock = useSelect(
		( select ) =>
			select( blockEditorStore ).getBlockParentsByBlockName(
				clientId,
				'woocommerce/single-product'
			).length > 0
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
			tag: null,
			cat: catId,
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
			tag: tagId,
			cat: null,
		} );
	}

	/**
	 * Case 2.3: GENERIC TAXONOMY
	 * Generic Taxonomy template
	 */

	const genericTaxonomyTemplateSlugs = [
		'taxonomy-product_cat',
		'taxonomy-product_tag',
		'tag',
		'category',
	];
	if ( genericTaxonomyTemplateSlugs.includes( templateSlug ) ) {
		return createLocationObject( 'archive', {
			tag: null,
			cat: null,
		} );
	}

	/**
	 * Case 3: GENERIC CART
	 * Cart/Checkout templates or Mini Cart
	 */

	if ( templateSlug === 'page-cart' || templateSlug === 'page-checkout' ) {
		return createLocationObject( 'cart', {
			productIds: [],
		} );
	}

	/**
	 * Case 4: GENERIC ORDER
	 * Order Confirmation template
	 */

	if ( templateSlug === 'order-confirmation' ) {
		return createLocationObject( 'order', {
			orderId: null,
		} );
	}

	/**
	 * Case 5: GENERIC
	 * All other cases
	 */

	return createLocationObject( 'generic', {} );
};
