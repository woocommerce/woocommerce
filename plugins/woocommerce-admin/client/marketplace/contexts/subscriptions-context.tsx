/**
 * External dependencies
 */
import { useState, createContext, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SubscriptionsContextType } from './types';
import { Subscription } from '../components/my-subscriptions/types';
import { fetchSubscriptions } from '../utils/functions';

export const SubscriptionsContext = createContext< SubscriptionsContextType >( {
	subscriptions: [],
	setSubscriptions: () => {},
	loadSubscriptions: () => new Promise( () => {} ),
	isLoading: true,
	setIsLoading: () => {},
	installingProducts: [],
	addInstalling: () => {},
	removeInstalling: () => {},
} );

export function SubscriptionsContextProvider( props: {
	children: JSX.Element;
} ): JSX.Element {
	const [ subscriptions, setSubscriptions ] = useState<
		Array< Subscription >
	>( [] );
	const [ isLoading, setIsLoading ] = useState( true );

	const loadSubscriptions = ( toggleLoading?: boolean ) => {
		if ( toggleLoading === true ) {
			setIsLoading( true );
		}

		return fetchSubscriptions()
			.then( ( subscriptionResponse ) => {
				setSubscriptions( subscriptionResponse );
			} )
			.finally( () => {
				if ( toggleLoading ) {
					setIsLoading( false );
				}
			} );
	};

	const [ installingProducts, setInstalling ] = useState< Array< string > >(
		[]
	);

	const addInstalling = ( productKey: string ) => {
		if ( installingProducts.includes( productKey ) ) {
			return;
		}
		const newInstalling = [ ...installingProducts, productKey ];
		setInstalling( newInstalling );
	};

	const removeInstalling = ( productKey: string ) => {
		const newInstalling = [ ...installingProducts ];
		const index = newInstalling.indexOf( productKey );
		if ( index > -1 ) {
			newInstalling.splice( index, 1 );
		}
		setInstalling( newInstalling );
	};

	useEffect( () => {
		loadSubscriptions( true );
	}, [] );

	const contextValue = {
		subscriptions,
		setSubscriptions,
		loadSubscriptions: () => loadSubscriptions(),
		isLoading,
		setIsLoading,
		installingProducts,
		addInstalling,
		removeInstalling,
	};

	return (
		<SubscriptionsContext.Provider value={ contextValue }>
			{ props.children }
		</SubscriptionsContext.Provider>
	);
}
