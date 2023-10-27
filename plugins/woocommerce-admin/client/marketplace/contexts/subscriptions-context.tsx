/**
 * External dependencies
 */
import {
	useState,
	createContext,
	useEffect,
	useMemo,
	useCallback,
} from '@wordpress/element';

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
	isInstalling: () => false,
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

	const isInstalling = ( productKey: string ) => {
		console.debug( productKey, installingProducts );
		return installingProducts.includes( productKey );
	};
	const addInstalling = ( productKey: string ) => {
		setInstalling( [ ...installingProducts, productKey ] );
	};

	const removeInstalling = ( productKey: string ) => {
		setInstalling( installingProducts.filter( ( p ) => p !== productKey ) );
	};

	useEffect( () => {
		loadSubscriptions( true );
	}, [] );

	const contextValue = {
		subscriptions,
		setSubscriptions,
		loadSubscriptions,
		isLoading,
		setIsLoading,
		isInstalling,
		addInstalling,
		removeInstalling,
	};

	return (
		<SubscriptionsContext.Provider value={ contextValue }>
			{ props.children }
		</SubscriptionsContext.Provider>
	);
}
