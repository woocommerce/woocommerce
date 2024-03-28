// @ts-nocheck

/**
 * External dependencies
 */

import { privateApis } from '@wordpress/interactivity/build';

/**
 * Internal dependencies
 */
import { init } from './router';

const consent =
	'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WordPress.';

const { directive, h: createElement, deepSignal } = privateApis( consent );

export {
	getContext,
	getElement,
	store,
	useEffect,
	useMemo,
} from '@wordpress/interactivity';
export { createElement, deepSignal, directive };
export { navigate, prefetch } from './router';
export { useContext } from 'preact/hooks';

document.addEventListener( 'DOMContentLoaded', async () => {
	await init();
} );
