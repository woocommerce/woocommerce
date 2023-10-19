/**
 * WordPress dependencies
 */
var consentString = 'I know using unstable features means my plugin or theme will inevitably break on the next WordPress release.';
if (window.wcSettings.wpVersion.indexOf('6.4') === 0) {
	consentString = 'I know using unstable features means my theme or plugin will inevitably break in the next version of WordPress.';
} 

import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';
export const {
  lock,
  unlock
} = __dangerousOptInToUnstableAPIsOnlyForCoreModules(consentString, '@wordpress/edit-site');
//# sourceMappingURL=lock-unlock.js.map