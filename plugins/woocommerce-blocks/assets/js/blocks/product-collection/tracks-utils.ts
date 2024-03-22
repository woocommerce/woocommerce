/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

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

export const useTracksLocation = ( templateSlug: string ) => {
	const postType = useSelect( ( select ) => {
		// @ts-expect-error Type definitions are missing
		// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/selectors.d.ts
		return select( editorStore ).getCurrentPostType();
	}, [] );

	if ( postType === 'wp_template' ) {
		return templateSlugToTemplateMap[ templateSlug ] || Locations.OTHER;
	}

	if ( postType === Locations.PAGE || postType === Locations.POST ) {
		return postType;
	}

	return Locations.OTHER;
};
