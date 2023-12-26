/**
 * WordPress dependencies
 */
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';

const oldConsentString =
	'I know using unstable features means my plugin or theme will inevitably break on the next WordPress release.';
const newConsentString =
	'I know using unstable features means my theme or plugin will inevitably break in the next version of WordPress.';

let consentString = oldConsentString;
const wcSettings = window.wcSettings;
const admin = wcSettings?.admin;

const wpVersion = parseFloat( wcSettings?.wpVersion );
const gutenbergVersion = parseFloat( admin?.gutenberg_version );

// Use the new consent string if the WP version is 6.4 and above
if ( ! isNaN( wpVersion ) && wpVersion >= 6.4 ) {
	consentString = newConsentString;
}

// Users can install the latest Gutenberg plugin manually
// Use the new consent string for Gutenberg 16.9 and above
if ( ! isNaN( gutenbergVersion ) && gutenbergVersion >= 16.9 ) {
	consentString = newConsentString;
}

export const { lock, unlock } =
	__dangerousOptInToUnstableAPIsOnlyForCoreModules(
		consentString,
		'@wordpress/edit-site'
	);
//# sourceMappingURL=lock-unlock.js.map
