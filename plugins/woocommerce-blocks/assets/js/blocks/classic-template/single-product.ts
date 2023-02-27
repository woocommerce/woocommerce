/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

// TODO: Provide the blockified template for the single product page and adjust
// other functions accordingly.
const getBlockifiedTemplate = () => [];

const isConversionPossible = () => false;

const getDescriptionAllowingConversion = () => '';

const getDescriptionDisallowingConversion = ( templateTitle: string ) =>
	sprintf(
		/* translators: %s is the template title */
		__(
			'This block serves as a placeholder for your %s. It will display the actual product image, title, price in your store. You can move this placeholder around and add more blocks around to customize the template.',
			'woo-gutenberg-products-block'
		),
		templateTitle
	);

const getDescription = ( templateTitle: string, canConvert: boolean ) => {
	if ( canConvert ) {
		return getDescriptionAllowingConversion();
	}

	return getDescriptionDisallowingConversion( templateTitle );
};

const getButtonLabel = () => '';

export {
	getBlockifiedTemplate,
	isConversionPossible,
	getDescription,
	getButtonLabel,
};
