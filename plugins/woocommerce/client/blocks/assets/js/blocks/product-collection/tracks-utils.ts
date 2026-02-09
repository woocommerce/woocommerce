/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { CORE_EDITOR_STORE } from '@woocommerce/utils';

enum Locations {
	SINGLE_PRODUCT = 'single-product',
	PRODUCT_CATALOG = 'product-catalog',
	PRODUCT_ARCHIVE = 'product-archive',
	ORDER_CONFIRMATION = 'order-confirmation',
	CART = 'cart',
	CHECKOUT = 'checkout',
	POST = 'post',
	PAGE = 'page',
	OTHER = 'other',
}
const templateSlugToTemplateMap: {
	[ key: string ]: Locations | undefined;
} = {
	'single-product': Locations.SINGLE_PRODUCT,
	'archive-product': Locations.PRODUCT_CATALOG,
	'taxonomy-product_cat': Locations.PRODUCT_ARCHIVE,
	'taxonomy-product_tag': Locations.PRODUCT_ARCHIVE,
	'taxonomy-product_attribute': Locations.PRODUCT_ARCHIVE,
	'product-search-results': Locations.PRODUCT_ARCHIVE,
	'order-confirmation': Locations.ORDER_CONFIRMATION,
	'page-cart': Locations.CART,
	'page-checkout': Locations.CHECKOUT,
};

export const useTracksLocation = ( templateSlug: string | undefined ) => {
	const postType = useSelect( ( select ) => {
		const editor = select( CORE_EDITOR_STORE );
		// @ts-expect-error Type definitions are missing
		// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/selectors.d.ts
		return editor?.getCurrentPostType?.();
	}, [] );

	if ( postType === Locations.PAGE || postType === Locations.POST ) {
		return postType;
	}

	if ( ! templateSlug ) {
		return Locations.OTHER;
	}

	const template = templateSlugToTemplateMap[ templateSlug ];

	if ( template ) {
		return template;
	}

	if ( templateSlug.includes( 'single-product' ) ) {
		return Locations.SINGLE_PRODUCT;
	}

	if (
		templateSlug.includes( 'taxonomy-product_cat' ) ||
		templateSlug.includes( 'taxonomy-product_tag' )
	) {
		return Locations.PRODUCT_ARCHIVE;
	}

	return Locations.OTHER;
};
