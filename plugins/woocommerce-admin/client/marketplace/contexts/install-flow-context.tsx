/**
 * External dependencies
 */
import { useState, createContext, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { InstallFlowContextType } from './types';
import { getAdminSetting } from '../../utils/admin-settings';
import { Product } from '~/marketplace/components/product-list/types';

export const InstallFlowContext = createContext< InstallFlowContextType >( {
	isConnected: false,
	setProduct: () => {},
} );

export function InstallFlowContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ isConnected, setIsConnected ] = useState( false );
	const [ product, setProduct ] = useState< Product >();

	useEffect( () => {
		const wccomSettings = getAdminSetting( 'wccomHelper', {} );
		setIsConnected( wccomSettings.isConnected ?? false );
	}, [] );

	const contextValue = {
		isConnected,
		product,
		setProduct,
	};

	return (
		<InstallFlowContext.Provider value={ contextValue }>
			{ props.children }
		</InstallFlowContext.Provider>
	);
}
