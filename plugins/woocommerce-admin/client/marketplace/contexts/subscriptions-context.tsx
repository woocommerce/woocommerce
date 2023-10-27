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

	const isInstalling = useCallback(
		( productKey: string ) => {
			return installingProducts.includes( productKey );
		},
		[ installingProducts ]
	);
	const addInstalling = useCallback(
		( productKey: string ) => {
			setInstalling( [ ...installingProducts, productKey ] );
		},
		[ installingProducts ]
	);

	const removeInstalling = useCallback(
		( productKey: string ) => {
			setInstalling(
				installingProducts.filter( ( p ) => p !== productKey )
			);
		},
		[ installingProducts ]
	);

	useEffect( () => {
		loadSubscriptions( true );
	}, [] );

	const contextValue = useMemo(
		() => ( {
			subscriptions,
			setSubscriptions,
			loadSubscriptions,
			isLoading,
			setIsLoading,
			isInstalling,
			addInstalling,
			removeInstalling,
		} ),
		[
			subscriptions,
			isLoading,
			isInstalling,
			addInstalling,
			removeInstalling,
		]
	);

	return (
		<SubscriptionsContext.Provider value={ contextValue }>
			{ props.children }
		</SubscriptionsContext.Provider>
	);
}
