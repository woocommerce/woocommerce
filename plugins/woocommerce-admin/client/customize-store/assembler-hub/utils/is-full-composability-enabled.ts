/**
 * External dependencies
 */
import {
	BlockPopover,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/block-editor';

export const isPatternToolkitFullComposabilityFeatureFlagEnabled = () => {
	return window.wcAdminFeatures[ 'pattern-toolkit-full-composability' ];
};

export const isGutenbergAPIAvailableForFullComposability = () => {
	return [ BlockPopover ].filter( Boolean ).length > 0;
};

export const isFullComposabilityFeatureAndAPIAvailable = () => {
	return (
		isPatternToolkitFullComposabilityFeatureFlagEnabled() &&
		isGutenbergAPIAvailableForFullComposability()
	);
};
