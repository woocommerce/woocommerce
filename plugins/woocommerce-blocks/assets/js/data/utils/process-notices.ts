/**
 * External dependencies
 */
import {
	WCNotice,
	WCNoticeList,
} from '@woocommerce/type-defs/api-error-response';
import { isObject, isString } from '@woocommerce/types';
import { createNotice } from '@woocommerce/base-utils';
import { select } from '@wordpress/data';

export const processNotices = ( notices: WCNoticeList ) => {
	if ( ! isObject( notices ) ) {
		return;
	}

	const validContexts = select(
		'wc/store/store-notices'
	).getRegisteredContainers();

	// Map server-side notices to client-side equivalents.
	const noticeTypeMap: [
		keyof WCNoticeList,
		'info' | 'error' | 'success'
	][] = [
		[ 'notice', 'info' ],
		[ 'success', 'success' ],
		[ 'error', 'error' ],
	];

	// Loop through each type of notice (notice, success, and error) and create client-side notices.
	noticeTypeMap.forEach( ( [ serverSideType, clientSideType ] ) => {
		if ( ! Array.isArray( notices[ serverSideType ] ) ) {
			return;
		}

		const noticesOfType = notices[ serverSideType ] as WCNotice[];

		noticesOfType.forEach( ( notice: WCNotice ) => {
			if ( ! isString( notice?.notice ) ) {
				return;
			}

			if (
				! isString( notice?.data?.context ) ||
				! validContexts.includes( notice?.data?.context )
			) {
				return;
			}

			createNotice( clientSideType, notice.notice, {
				context: notice?.data?.context || 'wc/checkout',
			} );
		} );
	} );
};
