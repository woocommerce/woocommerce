import type { SettingsValue } from './types';

export const isCheckboxChecked = (
	value: SettingsValue | undefined
): boolean => value === true || value === 1 || value === 'yes' || value === '1';

export const toCheckboxValue = (
	checked: boolean,
	initialValue: SettingsValue | undefined
): SettingsValue => {
	if (
		typeof initialValue !== 'undefined' &&
		checked === isCheckboxChecked( initialValue )
	) {
		return initialValue;
	}

	if ( typeof initialValue === 'boolean' ) {
		return checked;
	}

	if ( initialValue === 1 || initialValue === 0 ) {
		return checked ? 1 : 0;
	}

	if ( initialValue === '1' || initialValue === '0' ) {
		return checked ? '1' : '0';
	}

	return checked ? 'yes' : 'no';
};
