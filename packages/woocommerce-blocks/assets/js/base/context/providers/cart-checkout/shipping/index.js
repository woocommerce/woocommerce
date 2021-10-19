/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useReducer,
	useEffect,
	useMemo,
	useRef,
} from '@wordpress/element';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { deriveSelectedShippingRates } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { ERROR_TYPES, DEFAULT_SHIPPING_CONTEXT_DATA } from './constants';
import { hasInvalidShippingAddress } from './utils';
import { errorStatusReducer } from './reducers';
import {
	EMIT_TYPES,
	emitterObservers,
	reducer as emitReducer,
	emitEvent,
} from './event-emit';
import { useCheckoutContext } from '../checkout-state';
import { useCustomerDataContext } from '../customer';
import { useStoreCart } from '../../../hooks/cart/use-store-cart';
import { useSelectShippingRates } from '../../../hooks/shipping/use-select-shipping-rates';

/**
 * @typedef {import('@woocommerce/type-defs/contexts').ShippingDataContext} ShippingDataContext
 * @typedef {import('react')} React
 */

const { NONE, INVALID_ADDRESS, UNKNOWN } = ERROR_TYPES;
const ShippingDataContext = createContext( DEFAULT_SHIPPING_CONTEXT_DATA );

/**
 * @return {ShippingDataContext} Returns data and functions related to shipping methods.
 */
export const useShippingDataContext = () => {
	return useContext( ShippingDataContext );
};

/**
 * The shipping data provider exposes the interface for shipping in the checkout/cart.
 *
 * @param {Object} props Incoming props for provider
 * @param {React.ReactElement} props.children
 */
export const ShippingDataProvider = ( { children } ) => {
	const { dispatchActions } = useCheckoutContext();
	const { shippingAddress, setShippingAddress } = useCustomerDataContext();
	const {
		cartNeedsShipping: needsShipping,
		cartHasCalculatedShipping: hasCalculatedShipping,
		shippingRates,
		shippingRatesLoading,
		cartErrors,
	} = useStoreCart();
	const { selectShippingRate, isSelectingRate } = useSelectShippingRates();
	const [ shippingErrorStatus, dispatchErrorStatus ] = useReducer(
		errorStatusReducer,
		NONE
	);
	const [ observers, observerDispatch ] = useReducer( emitReducer, {} );
	const currentObservers = useRef( observers );
	const eventObservers = useMemo(
		() => ( {
			onShippingRateSuccess: emitterObservers( observerDispatch )
				.onSuccess,
			onShippingRateFail: emitterObservers( observerDispatch ).onFail,
			onShippingRateSelectSuccess: emitterObservers( observerDispatch )
				.onSelectSuccess,
			onShippingRateSelectFail: emitterObservers( observerDispatch )
				.onSelectFail,
		} ),
		[ observerDispatch ]
	);

	// set observers on ref so it's always current.
	useEffect( () => {
		currentObservers.current = observers;
	}, [ observers ] );

	// set selected rates on ref so it's always current.
	const selectedRates = useRef( () =>
		deriveSelectedShippingRates( shippingRates )
	);
	useEffect( () => {
		const derivedSelectedRates = deriveSelectedShippingRates(
			shippingRates
		);
		if ( ! isShallowEqual( selectedRates.current, derivedSelectedRates ) ) {
			selectedRates.current = derivedSelectedRates;
		}
	}, [ shippingRates ] );

	// increment/decrement checkout calculating counts when shipping is loading.
	useEffect( () => {
		if ( shippingRatesLoading ) {
			dispatchActions.incrementCalculating();
		} else {
			dispatchActions.decrementCalculating();
		}
	}, [ shippingRatesLoading, dispatchActions ] );

	// increment/decrement checkout calculating counts when shipping rates are being selected.
	useEffect( () => {
		if ( isSelectingRate ) {
			dispatchActions.incrementCalculating();
		} else {
			dispatchActions.decrementCalculating();
		}
	}, [ isSelectingRate, dispatchActions ] );

	// set shipping error status if there are shipping error codes
	useEffect( () => {
		if (
			cartErrors.length > 0 &&
			hasInvalidShippingAddress( cartErrors )
		) {
			dispatchErrorStatus( { type: INVALID_ADDRESS } );
		} else {
			dispatchErrorStatus( { type: NONE } );
		}
	}, [ cartErrors ] );

	const currentErrorStatus = useMemo(
		() => ( {
			isPristine: shippingErrorStatus === NONE,
			isValid: shippingErrorStatus === NONE,
			hasInvalidAddress: shippingErrorStatus === INVALID_ADDRESS,
			hasError:
				shippingErrorStatus === UNKNOWN ||
				shippingErrorStatus === INVALID_ADDRESS,
		} ),
		[ shippingErrorStatus ]
	);

	// emit events.
	useEffect( () => {
		if (
			! shippingRatesLoading &&
			( shippingRates.length === 0 || currentErrorStatus.hasError )
		) {
			emitEvent(
				currentObservers.current,
				EMIT_TYPES.SHIPPING_RATES_FAIL,
				{
					hasInvalidAddress: currentErrorStatus.hasInvalidAddress,
					hasError: currentErrorStatus.hasError,
				}
			);
		}
	}, [
		shippingRates,
		shippingRatesLoading,
		currentErrorStatus.hasError,
		currentErrorStatus.hasInvalidAddress,
	] );

	useEffect( () => {
		if (
			! shippingRatesLoading &&
			shippingRates.length > 0 &&
			! currentErrorStatus.hasError
		) {
			emitEvent(
				currentObservers.current,
				EMIT_TYPES.SHIPPING_RATES_SUCCESS,
				shippingRates
			);
		}
	}, [ shippingRates, shippingRatesLoading, currentErrorStatus.hasError ] );

	// emit shipping rate selection events.
	useEffect( () => {
		if ( isSelectingRate ) {
			return;
		}
		if ( currentErrorStatus.hasError ) {
			emitEvent(
				currentObservers.current,
				EMIT_TYPES.SHIPPING_RATE_SELECT_FAIL,
				{
					hasError: currentErrorStatus.hasError,
					hasInvalidAddress: currentErrorStatus.hasInvalidAddress,
				}
			);
		} else {
			emitEvent(
				currentObservers.current,
				EMIT_TYPES.SHIPPING_RATE_SELECT_SUCCESS,
				selectedRates.current
			);
		}
	}, [
		isSelectingRate,
		currentErrorStatus.hasError,
		currentErrorStatus.hasInvalidAddress,
	] );

	/**
	 * @type {ShippingDataContext}
	 */
	const ShippingData = {
		shippingErrorStatus: currentErrorStatus,
		dispatchErrorStatus,
		shippingErrorTypes: ERROR_TYPES,
		shippingRates,
		shippingRatesLoading,
		selectedRates: selectedRates.current,
		setSelectedRates: selectShippingRate,
		isSelectingRate,
		shippingAddress,
		setShippingAddress,
		needsShipping,
		hasCalculatedShipping,
		...eventObservers,
	};

	return (
		<>
			<ShippingDataContext.Provider value={ ShippingData }>
				{ children }
			</ShippingDataContext.Provider>
		</>
	);
};
