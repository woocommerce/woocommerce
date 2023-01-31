export const PERMALINK_PRODUCT_REGEX = /%(?:postname|pagename)%/;

export const getPermalinkParts = ( permalink_template: string | undefined ) => {
	if ( ! permalink_template ) {
		return {};
	}

	const [ prefix, suffix ] = permalink_template.split(
		PERMALINK_PRODUCT_REGEX
	);

	return {
		prefix,
		suffix,
	};
};
