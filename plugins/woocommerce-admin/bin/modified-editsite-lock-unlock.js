/**
 * External dependencies
 */
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';

const wordPressConsentString = {
	6.4: 'I know using unstable features means my plugin or theme will inevitably break on the next WordPress release.',
	6.5: 'I know using unstable features means my theme or plugin will inevitably break in the next version of WordPress.',
	6.6: 'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.',
};

const gutenbergConsentString = {
	16.9: 'I know using unstable features means my theme or plugin will inevitably break in the next version of WordPress',
	18.7: 'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.',
};

let consentString;
const wcSettings = window.wcSettings;
const admin = wcSettings?.admin;

const wpVersion = parseFloat( wcSettings?.wpVersion );
const gutenbergVersion = parseFloat( admin?.gutenberg_version );

// Use the new consent string if the WP version is 6.4 and above
if ( ! isNaN( wpVersion ) && wpVersion >= 6.4 ) {
	consentString = wordPressConsentString[ wpVersion ];
}

// Use the new consent string if the Gutenberg version is 6.4 and abo

// Users can install the latest Gutenberg plugin manually
// Use the new consent string for Gutenberg 16.9 and above
if ( ! isNaN( gutenbergVersion ) ) {
	const gutenbergConsentStringValues = Object.keys(
		gutenbergConsentString
	).map( parseFloat );

	const gutenbergVersionKeys = gutenbergConsentStringValues.filter(
		( value ) => gutenbergVersion >= value
	);

	if ( gutenbergVersionKeys.length ) {
		consentString =
			gutenbergConsentString[ Math.max( ...gutenbergVersionKeys ) ];
	}
}

export const { lock, unlock } =
	__dangerousOptInToUnstableAPIsOnlyForCoreModules(
		consentString,
		'@wordpress/edit-site'
	);
//# sourceMappingURL=lock-unlock.js.map
