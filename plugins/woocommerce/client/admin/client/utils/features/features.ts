/**
 * Internal dependencies
 */
import { getAdminSetting } from '../admin-settings';
import { Feature } from './types';

const ADMIN_SETTINGS_FEATURES_NAME = 'features';

/**
 * Get the feature flag from admin settings.
 *
 * @param featureId The feature id
 * @return The feature flag
 */
export function getFeature( featureId: string ): Feature | undefined {
	const features = getAdminSetting( ADMIN_SETTINGS_FEATURES_NAME );
	return features && features[ featureId ];
}

/**
 * Returns if the feature is enabled.
 *
 * @param featureId The feature id
 * @return `true` or `false` if the given feature is enabled
 */
export function isFeatureEnabled( featureId: string ): boolean {
	const feature = getFeature( featureId );
	return Boolean( feature?.is_enabled );
}

/**
 * Returns if the feature is experimental.
 *
 * @param featureId The feature id
 * @return `true` or `false` if the given feature is experimental
 */
export function isFeatureExperimental( featureId: string ): boolean {
	const feature = getFeature( featureId );
	return Boolean( feature?.is_experimental );
}
