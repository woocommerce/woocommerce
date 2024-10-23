/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { Options } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { NoticeState, Notice, NoticeStatus } from './types';

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
							status: action.status,
							options: action.options,
						},
					},
				};
			case 'REMOVE_NOTICE':
				const notices = { ...state.notices };
				if ( notices[ action.productKey ] ) {
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
			status: NoticeStatus,
			options?: Partial< Options >
		) {
			return {
				type: 'ADD_NOTICE',
				productKey,
				message,
				status,
				options,
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
