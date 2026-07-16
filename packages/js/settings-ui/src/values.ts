/**
 * Internal dependencies
 */
import type { SettingsValue } from './types';

export const toStringValue = ( value: SettingsValue | undefined ) =>
	value === null || typeof value === 'undefined' ? '' : String( value );

// Legacy PHP settings express checkbox state as yes/no, 1/0, or booleans
// depending on the source.
export const isCheckedValue = ( value: SettingsValue | undefined ): boolean =>
	value === true || value === 1 || value === 'yes' || value === '1';
