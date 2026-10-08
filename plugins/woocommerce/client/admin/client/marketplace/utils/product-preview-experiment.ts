/**
 * Internal dependencies
 */
import { MARKETPLACE_HOST } from '../components/constants';

export const PRODUCT_PREVIEW_EXPERIMENT_NAME =
	'woocommerce_marketplace_product_preview_202610';

export const PRODUCT_PREVIEW_TREATMENT = 'treatment';

/**
 * WooCommerce.com order attribution stores this parameter on orders, so
 * purchases can be split by variation.
 */
export const PRODUCT_PREVIEW_VARIATION_PARAM = 'utm_term';

/**
 * Adds the variation to the WooCommerce.com links in the preview HTML, such as
 * "Buy now" and "See more". Other links are left as they are.
 */
export function addVariationToPreviewLinks(
	html: string,
	variation: string
): string {
	const { hostname: marketplaceHostname } = new URL( MARKETPLACE_HOST );
	const template = document.createElement( 'template' );
	template.innerHTML = html;

	template.content
		.querySelectorAll< HTMLAnchorElement >( 'a[href]' )
		.forEach( ( link ) => {
			let url: URL;
			try {
				url = new URL(
					link.getAttribute( 'href' ) ?? '',
					document.baseURI
				);
			} catch {
				return;
			}

			if ( url.hostname !== marketplaceHostname ) {
				return;
			}

			url.searchParams.set( PRODUCT_PREVIEW_VARIATION_PARAM, variation );
			link.setAttribute( 'href', url.toString() );
		} );

	return template.innerHTML;
}
