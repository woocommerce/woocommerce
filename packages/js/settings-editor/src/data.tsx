/**
 * External dependencies
 */
import { createElement, createContext, useState } from '@wordpress/element';

const initialData = window.wcSettings?.admin?.settingsData;
const initialSettingsScripts = window.wcSettings?.admin?.settingsScripts;

const SettingsDataContext = createContext< {
	settingsData: SettingsData;
	setSettingsData: ( settingsData: SettingsData ) => void;
	settingsScripts: Record< string, string[] >;
	setSettingsScripts: ( settingsScripts: Record< string, string[] > ) => void;
} >( {
	settingsData: initialData,
	setSettingsData: () => {},
	settingsScripts: {},
	setSettingsScripts: () => {},
} );

const SettingsDataProvider = ( {
	children,
}: {
	children: React.ReactNode;
} ) => {
	const [ settingsData, setSettingsData ] = useState( initialData );
	const [ settingsScripts, setSettingsScripts ] = useState(
		initialSettingsScripts
	);

	return (
		<SettingsDataContext.Provider
			value={ {
				settingsData,
				setSettingsData,
				settingsScripts,
				setSettingsScripts,
			} }
		>
			{ children }
		</SettingsDataContext.Provider>
	);
};

export { SettingsDataContext, SettingsDataProvider };
