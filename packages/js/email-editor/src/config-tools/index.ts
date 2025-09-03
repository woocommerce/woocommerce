export * from './block-config';
export * from './filters';
export * from './formats';
export * from './block-style';
export * from './block-variations';

/**
 * Internal dependencies
 */
import {
	resetBlockStyles,
	resetBlockVariations,
	resetFormats,
	clearAllEmailHooks,
	restoreAllModifiedBlockSettings,
} from '.';

export function cleanupConfigurationChanges(): void {
	restoreAllModifiedBlockSettings();
	resetBlockStyles();
	resetBlockVariations();
	resetFormats();
	clearAllEmailHooks();
}
