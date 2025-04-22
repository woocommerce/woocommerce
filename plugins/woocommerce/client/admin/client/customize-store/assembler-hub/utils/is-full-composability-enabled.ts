/**
 * External dependencies
 */
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { BlockPopover } from '@wordpress/block-editor';

const isPatternToolkitFullComposabilityFeatureFlagEnabled = () => {
	// @ts-expect-error temp fix
	if ( window.parent?.window.cys_aiFlow ) {
		return false;
	}

	return window.wcAdminFeatures[ 'pattern-toolkit-full-composability' ];
};

const isGutenbergAPIAvailableForFullComposability = () => {
	return [ BlockPopover ].every(
		( api ) => api !== undefined && api !== null
	);
};

export const isFullComposabilityFeatureAndAPIAvailable = () => {
	// @ts-expect-error temp fix
	if ( window.parent?.window.cys_aiFlow ) {
		return false;
	}

	return (
		isPatternToolkitFullComposabilityFeatureFlagEnabled() &&
		isGutenbergAPIAvailableForFullComposability()
	);
};
