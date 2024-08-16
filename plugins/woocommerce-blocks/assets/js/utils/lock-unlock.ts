/**
 * External dependencies
 */
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';

export const { lock, unlock } =
	__dangerousOptInToUnstableAPIsOnlyForCoreModules(
		'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.',
		// The only way to unlock a private API feature is by using a core module name.
		// Using private features in non core modules is not allowed.
		// See https://github.com/WordPress/gutenberg/blob/%40wordpress/private-apis%401.5.0/packages/private-apis/src/implementation.js#L86.
		'@wordpress/block-editor'
	);
