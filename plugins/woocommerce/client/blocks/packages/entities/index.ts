/**
 * External dependencies
 */
import { isExperimentalWcRestApiV4Enabled } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
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
