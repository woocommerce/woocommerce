/**
 * External dependencies
 */
import { createElement, createContext, useState } from '@wordpress/element';

const initialData = window.wcSettings?.admin?.settingsData;

const SettingsDataContext = createContext< {
	settingsData: SettingsData;
	setSettingsData: ( settingsData: SettingsData ) => void;
} >( { settingsData: initialData, setSettingsData: () => {} } );

const SettingsDataProvider = ( {
	children,
}: {
	children: React.ReactNode;
} ) => {
	const [ settingsData, setSettingsData ] = useState( initialData );

	return (
		<SettingsDataContext.Provider
			value={ { settingsData, setSettingsData } }
		>
			{ children }
		</SettingsDataContext.Provider>
	);
};

export { SettingsDataContext, SettingsDataProvider };
