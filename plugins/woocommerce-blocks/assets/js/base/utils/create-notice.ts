/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { Options as NoticeOptions } from '@wordpress/notices';
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { noticeContexts } from '../context/event-emit/utils';

export const DEFAULT_ERROR_MESSAGE = __(
	'Something went wrong. Please contact us to get assistance.',
	'woo-gutenberg-products-block'
);

export const hasStoreNoticesContainer = ( container: string ): boolean => {
	const containers = select( 'wc/store/store-notices' ).getContainers();
	return containers.includes( container );
};

const findParentContainer = ( container: string ): string => {
	if ( container.includes( noticeContexts.CHECKOUT + '/' ) ) {
		return noticeContexts.CHECKOUT;
	}
	if ( container.includes( noticeContexts.CART + '/' ) ) {
		return hasStoreNoticesContainer( noticeContexts.CART )
			? noticeContexts.CART
			: noticeContexts.CHECKOUT;
	}
	return container;
};

/**
 * Wrapper for @wordpress/notices createNotice.
 *
 * This is used to create the correct type of notice based on the provided context, and to ensure the notice container
 * exists first, otherwise it uses the default context instead.
 */
export const createNotice = (
	status: 'error' | 'warning' | 'info' | 'success',
	message: string,
	options: Partial< NoticeOptions >
) => {
	const noticeContext = options?.context;
	const suppressNotices =
		select( 'wc/store/payment' ).isExpressPaymentMethodActive();

	if ( suppressNotices || noticeContext === undefined ) {
		return;
	}

	const { createNotice: dispatchCreateNotice } = dispatch( 'core/notices' );

	dispatchCreateNotice( status, message, {
		isDismissible: true,
		...options,
		context: hasStoreNoticesContainer( noticeContext )
			? noticeContext
			: findParentContainer( noticeContext ),
	} );
};

/**
 * Creates a notice only if the Store Notice Container is visible.
 */
export const createNoticeIfVisible = (
	status: 'error' | 'warning' | 'info' | 'success',
	message: string,
	options: Partial< NoticeOptions >
) => {
	if ( options?.context && hasStoreNoticesContainer( options.context ) ) {
		createNotice( status, message, options );
	}
};

/**
 * Remove notices from all contexts.
 *
 * @todo Remove this when supported in Gutenberg.
 * @see https://github.com/WordPress/gutenberg/pull/44059
 */
export const removeAllNotices = () => {
	const containers = select( 'wc/store/store-notices' ).getContainers();
	const { removeNotice } = dispatch( 'core/notices' );
	const { getNotices } = select( 'core/notices' );

	containers.forEach( ( container ) => {
		getNotices( container ).forEach( ( notice ) => {
			removeNotice( notice.id, container );
		} );
	} );
};
