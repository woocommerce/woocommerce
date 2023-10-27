/**
 * External dependencies
 */
import {
	useState,
	createContext,
	useMemo,
	useContext,
	useCallback,
} from '@wordpress/element';

/**
 * Internal dependencies
 */
import { InstallContextType } from './types';

export const InstallContext = createContext< InstallContextType >( {
	installingProducts: [],
	setInstallingProducts: () => {},
} );

export function InstallContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ installing, setInstalling ] = useState< Array< string > >( [] );

	const installingProducts = useMemo( () => installing, [ installing ] );

	const setInstallingProducts = useCallback(
		( productKeys: string[] ) => {
			setInstalling( productKeys );
		},
		[ setInstalling ]
	);

	const contextValue = {
		installingProducts,
		setInstallingProducts,
	};

	return (
		<InstallContext.Provider value={ contextValue }>
			{ props.children }
		</InstallContext.Provider>
	);
}
