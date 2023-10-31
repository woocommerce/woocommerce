/**
 * External dependencies
 */
import { createReduxStore, dispatch, register } from '@wordpress/data';
import { Options } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { NoticeState, Notice } from './types';

const NOTICE_STORE_NAME = 'woocommerce-admin/subscription-notices';

const DEFAULT_STATE: NoticeState = {
	notices: {},
};

const store = createReduxStore( NOTICE_STORE_NAME, {
	reducer( state: NoticeState | undefined = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'ADD_NOTICE':
				return {
					...state,
					notices: {
						...state.notices,
						[ action.productKey ]: {
							productKey: action.productKey,
							message: action.message,
							options: action.options,
							timeout: action.timeout,
						},
					},
				};
			case 'REMOVE_NOTICE':
				const notices = { ...state.notices };
				if ( notices[ action.productKey ] ) {
					if ( notices[ action.productKey ].timeout ) {
						clearTimeout( notices[ action.productKey ].timeout );
					}
					delete notices[ action.productKey ];
				}
				return {
					...state,
					notices,
				};
		}

		return state;
	},
	actions: {
		addNotice(
			productKey: string,
			message: string,
			options?: Partial< Options >
		) {
			const timeout = setTimeout( () => {
				dispatch( store ).removeNotice( productKey );
			}, 5000 );
			return {
				type: 'ADD_NOTICE',
				productKey,
				message,
				options,
				timeout,
			};
		},
		removeNotice( productKey: string ) {
			return {
				type: 'REMOVE_NOTICE',
				productKey,
			};
		},
	},
	selectors: {
		notices( state: NoticeState | undefined ): Notice[] {
			if ( ! state ) {
				return [];
			}
			return Object.values( state.notices );
		},
		getNotice(
			state: NoticeState | undefined,
			productKey: string
		): Notice | undefined {
			if ( ! state ) {
				return;
			}
			return state.notices[ productKey ];
		},
	},
} );

register( store );

export { store as noticeStore, NOTICE_STORE_NAME };
