export * from './block-config';
export * from './filters';
export * from './formats';
export * from './block-style';
export * from './block-variations';

import {
	resetBlockStyles,
	resetBlockVariations,
	resetFormats,
	clearEmailFilters,
	restoreAllModifiedBlockSettings,
} from '.';

export function cleanupConfigurationChanges(): void {
	restoreAllModifiedBlockSettings();
	resetBlockStyles();
	resetBlockVariations();
	resetFormats();
	clearEmailFilters();
}
