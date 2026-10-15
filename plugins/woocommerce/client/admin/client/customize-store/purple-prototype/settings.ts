/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

export type PurpleContainer = 'task-page' | 'modal';

export type PurpleLayout = 'stacked-images-left';

export type PurpleState =
	'default' | 'installing' | 'installed' | 'activating' | 'active' | 'error';

export type PurplePrototypeSettings = {
	container: PurpleContainer;
	layout: PurpleLayout;
	state: 'real' | PurpleState;
	purpleSlug: string;
	purpleInstalled: boolean;
	purpleActive: boolean;
	activeTheme: {
		name: string;
		stylesheet: string;
		screenshot: string;
		isBlockTheme: boolean;
	};
	homeUrl: string;
	ajaxUrl: string;
	nonce: string;
};

const DEFAULT_SETTINGS: PurplePrototypeSettings = {
	container: 'task-page',
	layout: 'stacked-images-left',
	state: 'real',
	purpleSlug: 'purple',
	purpleInstalled: false,
	purpleActive: false,
	activeTheme: {
		name: 'Twenty Twenty-Four',
		stylesheet: 'twentytwentyfour',
		screenshot: '',
		isBlockTheme: true,
	},
	homeUrl: '/',
	ajaxUrl: '',
	nonce: '',
};

// Changes made on this page load. wc-admin switches screens without reloading, so the
// settings printed with the page would otherwise go stale after an install or activation.
let sessionChanges: Partial< PurplePrototypeSettings > = {};

/**
 * Prototype settings saved by the "Purple prototype" admin bar menu, plus the real theme state.
 */
export const getPurplePrototypeSettings = (): PurplePrototypeSettings => ( {
	...DEFAULT_SETTINGS,
	...getAdminSetting( 'purpleCysPrototype', {} ),
	...sessionChanges,
} );

/**
 * Record a change in Purple's state, so the next visit to the screen in this page load sees it.
 */
export const updatePurplePrototypeSettings = (
	changes: Partial< PurplePrototypeSettings >
) => {
	sessionChanges = { ...sessionChanges, ...changes };
};
