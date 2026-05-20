/**
 * Internal dependencies
 */
import { isExperimentalWcRestApiV4Enabled } from '../settings/blocks/feature-flags';
import {
	registerProductEntity,
	registerSettingsEntity,
} from './register-entities';

export * from './product';
export * from './settings';

registerProductEntity();

/**
 * Register the settings entity only when the experimental v4 REST API is enabled.
 */
if ( isExperimentalWcRestApiV4Enabled() ) {
	registerSettingsEntity();
}
