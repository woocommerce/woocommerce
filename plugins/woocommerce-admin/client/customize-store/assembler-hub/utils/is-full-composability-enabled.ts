/**
 * External dependencies
 */
import {
	BlockPopover,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/block-editor';

export const isPatternToolkitFullComposabilityFeatureFlagEnabled = () => {
	// @ts-expect-error temp fix
	if ( window.parent?.window.cys_aiFlow ) {
		return false;
	}

	return window.wcAdminFeatures[ 'pattern-toolkit-full-composability' ];
};

export const isGutenbergAPIAvailableForFullComposability = () => {
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
