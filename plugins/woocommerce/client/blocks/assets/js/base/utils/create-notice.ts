/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Options as NoticeOptions,
	store as noticesStore,
} from '@wordpress/notices';
import { select, dispatch } from '@wordpress/data';
import { CurriedSelectorsOf } from '@wordpress/data/build-types/types';

/**
 * Internal dependencies
 */
import { noticeContexts } from '../context/event-emit/utils';
import type { PaymentStoreDescriptor } from '../../data/payment';
import type { StoreNoticesStoreDescriptor } from '../../data/store-notices';

export const DEFAULT_ERROR_MESSAGE = __(
	'Something went wrong. Please contact us to get assistance.',
	'woocommerce'
);

/**
 * Returns a list of all notice contexts defined by Blocks.
 *
 * Contexts are defined in enum format, but this returns an array of strings instead.
 */
export const getNoticeContexts = () => {
	return Object.values( noticeContexts );
};

/**
 * Wrapper for @wordpress/notices createNotice.
 */
export const createNotice = (
	status: 'error' | 'warning' | 'info' | 'success',
	message: string,
	options: Partial< NoticeOptions >
) => {
	const noticeContext = options?.context;
	const selectors = select(
		'wc/store/payment'
	) as CurriedSelectorsOf< PaymentStoreDescriptor >;
	const suppressNotices = selectors.isExpressPaymentMethodActive();

	if ( suppressNotices || noticeContext === undefined ) {
		return;
	}

	dispatch( noticesStore ).createNotice( status, message, {
		isDismissible: true,
		...options,
		context: noticeContext,
	} );
};

/**
 * Remove notices from all contexts.
 *
 * @todo Remove this when supported in Gutenberg.
 * @see https://github.com/WordPress/gutenberg/pull/44059
 */
export const removeAllNotices = () => {
	const selectors = select(
		'wc/store/store-notices'
	) as CurriedSelectorsOf< StoreNoticesStoreDescriptor >;
	const containers = selectors.getRegisteredContainers();
	const { removeNotice } = dispatch( noticesStore );
	const { getNotices } = select( noticesStore );

	containers.forEach( ( container ) => {
		getNotices( container ).forEach( ( notice ) => {
			removeNotice( notice.id, container );
		} );
	} );
};

export const removeNoticesWithContext = ( context: string ) => {
	const { removeNotice } = dispatch( noticesStore );
	const { getNotices } = select( noticesStore );

	getNotices( context ).forEach( ( notice ) => {
		removeNotice( notice.id, context );
	} );
};
