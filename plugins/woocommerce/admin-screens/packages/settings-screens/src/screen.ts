/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Breadcrumb, SettingsScreen } from './definition';

export interface SettingsScreenConfig {
	/** The screen ID registered in PHP, such as `my-gateway` or `my-gateway/advanced`. */
	id: string;
	area: 'payments';
	title: string;
	/** REST path of the endpoint that reads and saves the screen's settings. */
	restPath: string;
	/** The screen a sub-page belongs to, linked from its breadcrumbs. */
	parent?: { id: string; title: string };
}

/**
 * Get the area's breadcrumb, which links to its classic settings page.
 *
 * A full URL makes Core's Breadcrumbs render a plain link, which reloads the page.
 */
export function getAreaBreadcrumb(): Breadcrumb {
	const url = new URL( window.location.href );
	url.search = new URLSearchParams( {
		page: 'wc-settings',
		tab: 'checkout',
	} ).toString();
	return { label: __( 'Payments', 'woocommerce' ), to: url.toString() };
}

/**
 * Describe a screen from an extension's configuration.
 */
export function getScreen( config: SettingsScreenConfig ): SettingsScreen {
	return {
		title: config.title,
		entity: {
			kind: `woo_settings_${ config.area }`,
			name: config.id,
			baseURL: config.restPath,
		},
		breadcrumbs: [
			getAreaBreadcrumb(),
			...( config.parent
				? [
						{
							label: config.parent.title,
							to: `/settings/${ config.parent.id }`,
						},
					]
				: [] ),
			{ label: config.title },
		],
	};
}
