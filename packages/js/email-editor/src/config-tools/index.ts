export * from './block-config';
export * from './filters';
export * from './formats';
export * from './block-style';
export * from './block-variations';

/**
 * Internal dependencies
 */
import { resetBlockStyles } from './block-style';
import { resetBlockVariations } from './block-variations';
import { resetFormats } from './formats';
import { clearAllEmailHooks } from './filters';
import { restoreAllModifiedBlockSettings } from './block-config';

export function cleanupConfigurationChanges(): void {
	restoreAllModifiedBlockSettings();
	resetBlockStyles();
	resetBlockVariations();
	resetFormats();
	clearAllEmailHooks();
}
