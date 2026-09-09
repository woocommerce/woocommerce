/**
 * Internal dependencies
 */
import { isExperimentalWcRestApiV4Enabled } from '../../../assets/js/settings/blocks/feature-flags';
import {
	registerProductEntity,
	registerSettingsEntity,
} from './register-entities';

export {
	isExternalProduct,
	isProductResponseItem,
	useProduct,
} from './deprecated-exports';

registerProductEntity();

if ( isExperimentalWcRestApiV4Enabled() ) {
	registerSettingsEntity();
}
