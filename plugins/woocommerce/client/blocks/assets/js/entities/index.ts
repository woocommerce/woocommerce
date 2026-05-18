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
registerSettingsEntity();
