/**
 * Internal dependencies
 */
import { registerBlockVariationForEmail } from '../../config-tools';

/**
 * Register a custom variation for the site logo block
 * This variation is used to display the site logo in the email editor by automatically adding some preset options.
 */
function registerCustomSiteLogoBlockVariation() {
	registerBlockVariationForEmail( 'core/site-logo', {
		name: 'site-logo-default',
		title: 'Site Logo',
		attributes: {
			align: 'center',
			width: 120, // set a default width for the site logo
		},
		isDefault: true, // set this as the default variation
	} );
}

/**
 * Enhance the Site Logo block.
 */
function enhanceSiteLogoBlock() {
	registerCustomSiteLogoBlockVariation();
}

export { enhanceSiteLogoBlock };
