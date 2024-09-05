/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';

export type PermalinkParts = {
	prefix: string | undefined;
	postName: string | undefined;
	suffix: string | undefined;
};

export const getPermalinkParts = ( product: Product ): PermalinkParts => {
	let postName, prefix, suffix;
	if ( product && product.permalink_template ) {
		postName = product.slug || product.generated_slug;
		[ prefix, suffix ] = product.permalink_template.split(
			/%(?:postname|pagename)%/
		);
	}
	return {
		prefix,
		postName,
		suffix,
	};
};
