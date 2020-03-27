/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useState,
	useReducer,
	useEffect,
	useRef,
} from '@wordpress/element';
import {
	useShippingAddress,
	useShippingRates,
	useStoreCart,
} from '@woocommerce/base-hooks';
import { useCheckoutContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import { ERROR_TYPES, DEFAULT_SHIPPING_CONTEXT_DATA } from './constants';
import {
	EMIT_TYPES,
	emitterSubscribers,
	reducer as emitReducer,
	emitEvent,
} from './event-emit';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').ShippingDataContext} ShippingDataContext
 */

const { NONE, INVALID_ADDRESS, UNKNOWN } = ERROR_TYPES;

const setStatusAction = ( status ) => ( {
	type: status,
} );

/**
 * Reducer for shipping status state
 *
 * @param {string} state  The current status.
 * @param {Object} action The incoming action.
 */
const errorStatusReducer = ( state, { type } ) => {
	if ( Object.keys( ERROR_TYPES ).includes( type ) ) {
		return state;
	}
	return type;
};

const ShippingDataContext = createContext( DEFAULT_SHIPPING_CONTEXT_DATA );

/**
 * @return {ShippingDataContext} Returns data and functions related to
 *                                     shipping methods.
 */
export const useShippingDataContext = () => {
	return useContext( ShippingDataContext );
};

/**
 * Calculating component for shipping rates.
 *
 * //@todo this is currently invalid because it's based on an older api.
 * // this will need to be updated once shipping rate work is complete.
 *
 * @param {Object} props Incoming props for the component
 */
const ShippingRateCalculation = ( { onChange } ) => {
	// @todo, it'd be handy if we could pass through callbacks that are fired on
	// successful rate retrieval vs callbacks fired on unsuccessful rates
	// retrieval. That way emitters could just be fed into the hook directly.
	const { shippingRates, shippingRatesLoading } = useShippingRates( [
		'country',
		'state',
		'city',
		'postcode',
	] );
	useEffect( () => {
		onChange( shippingRates, shippingRatesLoading );
	}, [ shippingRates, shippingRatesLoading ] );
	return null;
};

// @todo wire up checkout context needed here (like isCalculating etc)
// @todo useShippingRates needs to be wired up with error handling so we know
// when an invalid address is provided for it (because payment methods might
// provide an invalid address)
export const ShippingDataProvider = ( { children } ) => {
	const { dispatchActions } = useCheckoutContext();
	const { cartNeedsShipping: needsShipping } = useStoreCart();
	const [ shippingErrorStatus, dispatchErrorStatus ] = useReducer(
		errorStatusReducer,
		NONE
	);
	const [ observers, subscriber ] = useReducer( emitReducer, {} );
	const { shippingAddress, setShippingAddress } = useShippingAddress();
	const currentObservers = useRef( observers );
	const [ shippingOptions, setShippingOptions ] = useState( [] );
	const [ shippingOptionsLoading, setShippingOptionsLoading ] = useState(
		false
	);
	// @todo, this will need wired up to persistence (useSelectedRates?) which
	// will be setup similar to `useShippingRates` (or maybe in the same hook?)
	const [ selectedRates, setSelectedRates ] = useState( [] );
	const onShippingRateSuccess = emitterSubscribers( subscriber ).onSuccess;
	const onShippingRateFail = emitterSubscribers( subscriber ).onFail;
	const onShippingRateSelectSuccess = emitterSubscribers( subscriber )
		.onSelectSuccess;
	const onShippingRateSelectFail = emitterSubscribers( subscriber )
		.onShippingRateSelectFail;

	// set observers on ref so it's always current
	useEffect( () => {
		currentObservers.current = observers;
	}, [ observers ] );

	// increment/decrement checkout calculating counts when shipping is loading
	useEffect( () => {
		if ( shippingOptionsLoading ) {
			dispatchActions.incrementCalculating();
		} else {
			dispatchActions.decrementCalculating();
		}
	}, [ shippingOptionsLoading ] );

	// @todo need to add error handling to useShippingRates so that errors are
	// exposed. We need error types exposed by the error handling as well.
	// also we need to add similar logic for selection/unselection of rates and
	// emit the events (see emit events block)
	const onRateChange = ( shippingRates, shippingRatesLoading, error ) => {
		setShippingOptions( shippingRates );
		setShippingOptionsLoading( shippingRatesLoading );
		if ( ! shippingRatesLoading && error && error.type ) {
			// @todo this type might need normalizing to something recognized by
			// the ERROR_TYPE constants.
			dispatchErrorStatus( setStatusAction( error.type ) );
		} else if ( ! shippingRatesLoading && shippingRates ) {
			dispatchErrorStatus( NONE );
		}
	};

	const currentErrorStatus = {
		isPristine: shippingErrorStatus === NONE,
		isValid: shippingErrorStatus === NONE,
		hasInvalidAddress: shippingErrorStatus === INVALID_ADDRESS,
		hasError:
			shippingErrorStatus === UNKNOWN ||
			shippingErrorStatus === INVALID_ADDRESS,
	};

	// emit events
	// @todo add emitters for shipping rate selection.
	useEffect( () => {
		if ( ! shippingOptionsLoading && currentErrorStatus.hasError ) {
			emitEvent(
				currentObservers.current,
				EMIT_TYPES.SHIPPING_RATES_SUCCESS,
				shippingErrorStatus
			);
		} else if ( ! shippingOptionsLoading && shippingOptions ) {
			emitEvent(
				currentObservers.current,
				EMIT_TYPES.SHIPPING_RATES_SUCCESS,
				shippingOptions
			);
		}
	}, [
		shippingOptions,
		shippingOptionsLoading,
		currentErrorStatus,
		shippingErrorStatus,
	] );

	/**
	 * @type {ShippingDataContext}
	 */
	const ShippingData = {
		shippingErrorStatus,
		dispatchErrorStatus,
		shippingErrorTypes: ERROR_TYPES,
		shippingRates: shippingOptions,
		setShippingRates: setShippingOptions,
		shippingRatesLoading: shippingOptionsLoading,
		selectedRates,
		setSelectedRates,
		shippingAddress,
		setShippingAddress,
		onShippingRateSuccess,
		onShippingRateFail,
		onShippingRateSelectSuccess,
		onShippingRateSelectFail,
		needsShipping,
	};
	return (
		<>
			<ShippingRateCalculation
				address={ shippingAddress }
				onChange={ onRateChange }
			/>
			<ShippingDataContext.Provider value={ ShippingData }>
				{ children }
			</ShippingDataContext.Provider>
		</>
	);
};
