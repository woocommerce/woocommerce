/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useState, useEffect } from '@wordpress/element';
// import { store as editorStore } from '@wordpress/editor';
import { isNumber } from '@woocommerce/types';

type LocationType = 'product' | 'archive' | 'cart' | 'order' | 'generic';
const parseProductResponse = (
	resp?: Record< 'id', number >[]
): number | null => ( resp && resp.length ? resp[ 0 ]?.id : null );

const createLocationObject = (
	type: LocationType,
	sourceData: { [ key: string ]: unknown }
) => ( {
	type,
	sourceData,
} );
export const useGetLocation = ( context ) => {
	const [ productId, setProductId ] = useState< number | null >( null );
	const { templateSlug, postId } = context;

	// const currentTemplateId = useSelect( ( select ) => {
	// 	return select( editorStore ).getCurrentPostId();
	// } );

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
			const [ , slug ] = templateSlug.split( 'single-product-' );
			const getProductId = async () => {
				const response = ( await resolveSelect(
					coreStore
				).getEntityRecords( 'postType', 'product', {
					_fields: [ 'id' ],
					slug,
				} ) ) as Record< 'id', number >[];
				const productId = parseProductResponse( response );
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
		return createLocationObject( 'archive', {
			tag: 'TODO',
			cat: 'TODO',
			attr: 'TODO',
		} );
	}
	// Case 2.2: Taxonomy context, specific ID - Products by * Templates
	if ( templateSlug === 'taxonomy-product_cat' ) {
		return createLocationObject( 'archive', {
			tag: null,
			cat: null,
			attr: null,
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
