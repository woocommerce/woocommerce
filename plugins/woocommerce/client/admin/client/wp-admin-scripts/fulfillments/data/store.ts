/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createReduxStore, register } from '@wordpress/data';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { Order, Fulfillment, Refund } from './types';

export const STORE_NAME = 'order/fulfillments';

const getFulfillmentErrorMessage = (
	error: unknown,
	defaultMessage: string
): string => {
	if (
		error &&
		typeof error === 'object' &&
		'message' in error &&
		'code' in error
	) {
		const apiError = error as { message: string; code: string };
		if ( apiError.code === 'woocommerce_fulfillment_error' ) {
			return apiError.message;
		}
	}
	return defaultMessage;
};

const actionTypes = {
	SET_ORDER: 'SET_ORDER',
	SET_REFUNDS: 'SET_REFUNDS',
	SET_LOADING: 'SET_LOADING',
	SET_ERROR: 'SET_ERROR',
	SET_FULFILLMENTS: 'SET_FULFILLMENTS',
	SET_FULFILLMENT: 'SET_FULFILLMENT',
	DELETE_FULFILLMENT: 'DELETE_FULFILLMENT',
} as const;

interface OrderState {
	order: Order | null;
	refunds: Refund[];
	fulfillments: Fulfillment[];
	loading: boolean;
	error: string | null;
}

const DEFAULT_STATE: { orderMap: Record< string, OrderState > } = {
	orderMap: {},
};

const getInitialOrderState = (): OrderState => ( {
	order: null,
	refunds: [],
	fulfillments: [],
	loading: false,
	error: null,
} );

// --- Internal Action Creators
const internalActions = {
	setOrder( orderId: number, order: Order ) {
		return { type: actionTypes.SET_ORDER, orderId, order };
	},
	setRefunds( orderId: number, refunds: Refund[] ) {
		return { type: actionTypes.SET_REFUNDS, orderId, refunds };
	},
	setLoading( orderId: number, isLoading: boolean ) {
		return { type: actionTypes.SET_LOADING, orderId, isLoading };
	},
	setError( orderId: number, error: string | null ) {
		return { type: actionTypes.SET_ERROR, orderId, error };
	},
	setFulfillments( orderId: number, fulfillments: Fulfillment[] ) {
		return { type: actionTypes.SET_FULFILLMENTS, orderId, fulfillments };
	},
	setFulfillment(
		orderId: number,
		fulfillmentId: number,
		fulfillment: Fulfillment
	) {
		return {
			type: actionTypes.SET_FULFILLMENT,
			orderId,
			fulfillmentId,
			fulfillment,
		};
	},
	deleteFulfillmentRecord( orderId: number, fulfillmentId: number ) {
		return { type: actionTypes.DELETE_FULFILLMENT, orderId, fulfillmentId };
	},
};

// --- Public Async Actions
const publicActions = {
	saveFulfillment:
		(
			orderId: number,
			fulfillment: Fulfillment,
			notifyCustomer: boolean
		) =>
		async ( { dispatch }: { dispatch: typeof actions } ) => {
			dispatch.setLoading( orderId, true );
			dispatch.setError( orderId, null );
			try {
				const saved = await apiFetch< Fulfillment >( {
					path: addQueryArgs(
						`/wc/v3/orders/${ orderId }/fulfillments`,
						{ notify_customer: notifyCustomer }
					),
					method: 'POST',
					data: fulfillment,
				} );
				if ( ! saved.id ) {
					throw new Error( 'Fulfillment ID is missing in response' );
				}
				dispatch.setFulfillment( orderId, saved.id, saved );
			} catch ( error: unknown ) {
				dispatch.setError(
					orderId,
					getFulfillmentErrorMessage(
						error,
						'Failed to save fulfillment'
					)
				);
			} finally {
				dispatch.setLoading( orderId, false );
			}
		},

	updateFulfillment:
		(
			orderId: number,
			fulfillment: Fulfillment,
			notifyCustomer: boolean
		) =>
		async ( { dispatch }: { dispatch: typeof actions } ) => {
			dispatch.setLoading( orderId, true );
			dispatch.setError( orderId, null );
			if ( ! fulfillment.id ) {
				dispatch.setError( orderId, 'Fulfillment ID is required' );
				dispatch.setLoading( orderId, false );
				return;
			}
			try {
				const updated = await apiFetch< Fulfillment >( {
					path: addQueryArgs(
						`/wc/v3/orders/${ orderId }/fulfillments/${ fulfillment.id }`,
						{ notify_customer: notifyCustomer }
					),
					method: 'PUT',
					data: fulfillment,
				} );
				if ( ! updated.id ) {
					throw new Error( 'Fulfillment ID is missing in response' );
				}
				dispatch.setFulfillment( orderId, updated.id, updated );
			} catch ( error: unknown ) {
				dispatch.setError(
					orderId,
					getFulfillmentErrorMessage(
						error,
						'Failed to update fulfillment'
					)
				);
			} finally {
				dispatch.setLoading( orderId, false );
			}
		},

	deleteFulfillment:
		( orderId: number, fulfillmentId: number, notifyCustomer: boolean ) =>
		async ( { dispatch }: { dispatch: typeof actions } ) => {
			dispatch.setLoading( orderId, true );
			dispatch.setError( orderId, null );
			try {
				await apiFetch( {
					path: addQueryArgs(
						`/wc/v3/orders/${ orderId }/fulfillments/${ fulfillmentId }`,
						{ notify_customer: notifyCustomer }
					),
					method: 'DELETE',
				} );
				dispatch.deleteFulfillmentRecord( orderId, fulfillmentId );
			} catch ( error: unknown ) {
				dispatch.setError(
					orderId,
					getFulfillmentErrorMessage(
						error,
						'Failed to delete fulfillment'
					)
				);
			} finally {
				dispatch.setLoading( orderId, false );
			}
		},
};

const actions = {
	...internalActions,
	...publicActions,
};

type Action = ReturnType<
	( typeof internalActions )[ keyof typeof internalActions ]
>;

// --- Reducer
function reducer( state = DEFAULT_STATE, action: Action ) {
	const prev = state.orderMap[ action.orderId ] || getInitialOrderState();

	switch ( action.type ) {
		case actionTypes.SET_ORDER:
			return {
				...state,
				orderMap: {
					...state.orderMap,
					[ action.orderId ]: { ...prev, order: action.order },
				},
			};
		case actionTypes.SET_REFUNDS:
			return {
				...state,
				orderMap: {
					...state.orderMap,
					[ action.orderId ]: {
						...prev,
						refunds: action.refunds,
					},
				},
			};
		case actionTypes.SET_LOADING:
			return {
				...state,
				orderMap: {
					...state.orderMap,
					[ action.orderId ]: { ...prev, loading: action.isLoading },
				},
			};
		case actionTypes.SET_ERROR:
			return {
				...state,
				orderMap: {
					...state.orderMap,
					[ action.orderId ]: { ...prev, error: action.error },
				},
			};
		case actionTypes.SET_FULFILLMENTS:
			return {
				...state,
				orderMap: {
					...state.orderMap,
					[ action.orderId ]: {
						...prev,
						fulfillments: action.fulfillments,
					},
				},
			};
		case actionTypes.SET_FULFILLMENT:
			return {
				...state,
				orderMap: {
					...state.orderMap,
					[ action.orderId ]: {
						...prev,
						fulfillments: [
							...prev.fulfillments.filter(
								( f ) => f.id !== action.fulfillmentId
							),
							action.fulfillment,
						],
					},
				},
			};
		case actionTypes.DELETE_FULFILLMENT:
			return {
				...state,
				orderMap: {
					...state.orderMap,
					[ action.orderId ]: {
						...prev,
						fulfillments: prev.fulfillments.filter(
							( f ) => f.id !== action.fulfillmentId
						),
					},
				},
			};
		default:
			return state;
	}
}

// --- Selectors
const selectors = {
	getState( state: typeof DEFAULT_STATE ) {
		return state;
	},
	getOrder( state: typeof DEFAULT_STATE, orderId: number ) {
		return state.orderMap[ orderId ]?.order;
	},
	getRefunds( state: typeof DEFAULT_STATE, orderId: number ) {
		return state.orderMap[ orderId ]?.refunds || [];
	},
	isLoading( state: typeof DEFAULT_STATE, orderId: number ) {
		return !! state.orderMap[ orderId ]?.loading;
	},
	getError( state: typeof DEFAULT_STATE, orderId: number ) {
		return state.orderMap[ orderId ]?.error || null;
	},
	readFulfillments( state: typeof DEFAULT_STATE, orderId: number ) {
		return state.orderMap[ orderId ]?.fulfillments || [];
	},
	readFulfillment(
		state: typeof DEFAULT_STATE,
		orderId: number,
		fulfillmentId: number
	) {
		return (
			state.orderMap[ orderId ]?.fulfillments?.find(
				( f ) => f.id === fulfillmentId
			) || null
		);
	},
};

// --- Resolvers
const resolvers = {
	getOrder:
		( orderId: number ) =>
		async ( { dispatch }: { dispatch: typeof actions } ) => {
			dispatch.setLoading( orderId, true );
			dispatch.setError( orderId, null );
			try {
				const order: Order = await apiFetch( {
					path: `/wc/v3/orders/${ orderId }`,
					method: 'GET',
				} );
				dispatch.setOrder( orderId, order );
				if ( order.refunds.length > 0 ) {
					const refunds: Refund[] = await apiFetch( {
						path: `/wc/v3/orders/${ orderId }/refunds`,
						method: 'GET',
					} );
					dispatch.setRefunds( orderId, refunds );
				}
			} catch ( error: unknown ) {
				dispatch.setError(
					orderId,
					error instanceof Error
						? error.message
						: 'Failed to load order'
				);
			} finally {
				dispatch.setLoading( orderId, false );
			}
		},
	readFulfillments:
		( orderId: number ) =>
		async ( { dispatch }: { dispatch: typeof actions } ) => {
			dispatch.setLoading( orderId, true );
			dispatch.setError( orderId, null );
			try {
				const fulfillments = await apiFetch< Fulfillment[] >( {
					path: `/wc/v3/orders/${ orderId }/fulfillments`,
					method: 'GET',
				} );
				dispatch.setFulfillments( orderId, fulfillments );
			} catch ( error: unknown ) {
				dispatch.setError(
					orderId,
					error instanceof Error
						? error.message
						: 'Failed to load fulfillments'
				);
			} finally {
				dispatch.setLoading( orderId, false );
			}
		},
};

// --- Store Registration
export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
	resolvers,
} );

register( store );
