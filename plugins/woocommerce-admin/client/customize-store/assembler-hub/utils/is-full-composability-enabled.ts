/**
 * External dependencies
 */
import {
	BlockPopover,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/block-editor';

const isPatternToolkitFullComposabilityFeatureFlagEnabled = () => {
	return window.wcAdminFeatures[ 'pattern-toolkit-full-composability' ];
};

const isGutenbergAPIAvailableForFullComposability = () => {
	return [ BlockPopover ].every(
		( api ) => api !== undefined && api !== null
	);
};

export const isFullComposabilityFeatureAndAPIAvailable = () => {
	return (
		isPatternToolkitFullComposabilityFeatureFlagEnabled() &&
		isGutenbergAPIAvailableForFullComposability()
	);
};
