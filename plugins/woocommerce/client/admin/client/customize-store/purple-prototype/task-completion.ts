/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import type { MouseEvent } from 'react';

/**
 * Mark the "Customize your store" task done. Any action on this screen counts.
 */
export const completeCustomizeStoreTask = () =>
	apiFetch( {
		path: '/wc-admin/options',
		method: 'POST',
		data: { woocommerce_admin_customize_store_completed: 'yes' },
	} ).catch( () => undefined );

/**
 * Click handler for links that complete the task. Same-tab links wait for the save first,
 * so the request isn't cut off by the page change.
 */
export const completeTaskThenNavigate = (
	event: MouseEvent< HTMLElement >,
	href: string,
	opensNewTab: boolean
) => {
	if ( opensNewTab || event.metaKey || event.ctrlKey || event.shiftKey ) {
		void completeCustomizeStoreTask();
		return;
	}

	event.preventDefault();
	void completeCustomizeStoreTask().finally( () => {
		window.location.href = href;
	} );
};
