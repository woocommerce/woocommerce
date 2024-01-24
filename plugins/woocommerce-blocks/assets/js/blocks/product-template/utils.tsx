/**
 * External dependencies
 */
import { resolveSelect, useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useState, useEffect } from '@wordpress/element';
// import { store as editorStore } from '@wordpress/editor';
import { isNumber } from '@woocommerce/types';

type LocationType = 'product' | 'archive' | 'cart' | 'order' | 'generic';

const parseResponse = ( resp?: Record< 'id', number >[] ): number | null =>
	resp && resp.length && resp[ 0 ]?.id ? resp[ 0 ].id : null;
const createGetEntitySlug =
	( templateSlug: string ) =>
	( taxonomySlug: string ): string =>
		templateSlug.replace( `${ taxonomySlug }-`, '' );

const createLocationObject = (
	type: LocationType,
	sourceData: { [ key: string ]: unknown }
) => ( {
	type,
	sourceData,
} );
export const useGetLocation = ( context ) => {
	const { templateSlug, postId } = context;

	const [ productId, setProductId ] = useState< number | null >( null );
	const [ catId, setCatId ] = useState< number | null >( null );
	const [ tagId, setTagId ] = useState< number | null >( null );

	const getEntitySlug = createGetEntitySlug( templateSlug );

	console.log( 'templateSlug', templateSlug );

	// Case 1.2: Product context, specific ID - Single Product Block
	if ( isNumber( postId ) ) {
		return createLocationObject( 'product', { productId: postId } );
	}

	// Case 1.2: Product context, specific ID - Specific Single Product Template
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

	// Case 1.1: Product context - Single Product Template
	if ( templateSlug === 'single-product' ) {
		return createLocationObject( 'product', { postId: null } );
	}

	// Case 2.1: Taxonomy context - Specific Taxonomy Template
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

	// Case 2.2: Taxonomy context - Products by * Templates
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
	// Case 3: Cart context:
	//           - Cart template
	//           - Checkout template
	//           - Mini Cart template part
	if ( templateSlug === 'page-cart' || templateSlug === 'page-checkout' ) {
		return createLocationObject( 'cart', {
			productIds: [],
		} );
	}

	// Case 4: Order context - Order confirmation template
	if ( templateSlug === 'order-confirmation' ) {
		return createLocationObject( 'order', {
			orderId: null,
		} );
	}
	// Case 5: Generic context - Others
	return createLocationObject( 'generic', {} );
};
