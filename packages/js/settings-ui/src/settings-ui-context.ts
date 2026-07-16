/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { SettingsUIPageContextValue } from './types';

export const SettingsUIPageContext =
	createContext< SettingsUIPageContextValue | null >( null );

/**
 * Page-level state for registered settings field components: the schema,
 * the page/section context, and the initial values. Field-level state
 * travels through the component props instead.
 */
export const useSettingsUIContext = (): SettingsUIPageContextValue => {
	const value = useContext( SettingsUIPageContext );

	if ( ! value ) {
		throw new Error(
			'useSettingsUIContext must be used within a settings UI page.'
		);
	}

	return value;
};
