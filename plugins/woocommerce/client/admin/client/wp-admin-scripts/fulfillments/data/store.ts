/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { Order, Fulfillment } from './types';

export const STORE_NAME = 'order/fulfillments';

const actionTypes = {
	SET_ORDER: 'SET_ORDER',
	SET_LOADING: 'SET_LOADING',
	SET_ERROR: 'SET_ERROR',
	SET_FULFILLMENTS: 'SET_FULFILLMENTS',
	SET_FULFILLMENT: 'SET_FULFILLMENT',
	DELETE_FULFILLMENT: 'DELETE_FULFILLMENT',
} as const;

interface ResponseWithFulfillment {
	fulfillment: Fulfillment & { id: number };
}
interface ResponseWithFulfillments {
	fulfillments: Fulfillment[];
}

interface OrderState {
	order: Order | null;
	fulfillments: Fulfillment[];
	loading: boolean;
	error: string | null;
}

const DEFAULT_STATE: { orderMap: Record< string, OrderState > } = {
	orderMap: {},
};

const getInitialOrderState = (): OrderState => ( {
	order: null,
	fulfillments: [],
	loading: false,
	error: null,
} );

// --- Internal Action Creators
const internalActions = {
	setOrder( orderId: number, order: Order ) {
		return { type: actionTypes.SET_ORDER, orderId, order };
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
		( orderId: number, fulfillment: Fulfillment ) =>
		async ( { dispatch }: { dispatch: typeof actions } ) => {
			dispatch.setLoading( orderId, true );
			dispatch.setError( orderId, null );
			try {
				const saved = await apiFetch< ResponseWithFulfillment >( {
					path: `/wc/v3/orders/${ orderId }/fulfillments`,
					method: 'POST',
					data: fulfillment,
				} );
				dispatch.setFulfillment(
					orderId,
					saved.fulfillment.id,
					saved.fulfillment
				);
			} catch ( error: unknown ) {
				dispatch.setError(
					orderId,
					error instanceof Error
						? error.message
						: 'Failed to save fulfillment'
				);
			} finally {
				dispatch.setLoading( orderId, false );
			}
		},

	updateFulfillment:
		( orderId: number, fulfillment: Fulfillment ) =>
		async ( { dispatch }: { dispatch: typeof actions } ) => {
			dispatch.setLoading( orderId, true );
			dispatch.setError( orderId, null );
			try {
				const updated = await apiFetch< ResponseWithFulfillment >( {
					path: `/wc/v3/orders/${ orderId }/fulfillments/${ fulfillment.id }`,
					method: 'PUT',
					data: fulfillment,
				} );
				dispatch.setFulfillment(
					orderId,
					updated.fulfillment.id,
					updated.fulfillment
				);
			} catch ( error: unknown ) {
				dispatch.setError(
					orderId,
					error instanceof Error
						? error.message
						: 'Failed to update fulfillment'
				);
			} finally {
				dispatch.setLoading( orderId, false );
			}
		},

	deleteFulfillment:
		( orderId: number, fulfillmentId: number ) =>
		async ( { dispatch }: { dispatch: typeof actions } ) => {
			dispatch.setLoading( orderId, true );
			dispatch.setError( orderId, null );
			try {
				await apiFetch( {
					path: `/wc/v3/orders/${ orderId }/fulfillments/${ fulfillmentId }`,
					method: 'DELETE',
				} );
				dispatch.deleteFulfillmentRecord( orderId, fulfillmentId );
			} catch ( error: unknown ) {
				dispatch.setError(
					orderId,
					error instanceof Error
						? error.message
						: 'Failed to delete fulfillment'
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
				const { fulfillments } =
					await apiFetch< ResponseWithFulfillments >( {
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
