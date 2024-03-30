/**
 * External dependencies
 */

import { privateApis } from '@wordpress/interactivity';

const consent =
	'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WordPress.';

const {
	directive,
	h: createElement,
	deepSignal,
	directivePrefix,
} = privateApis( consent );

console.log( directivePrefix );

export {
	getContext,
	getElement,
	store,
	useEffect,
	useMemo,
} from '@wordpress/interactivity';

export { createElement, deepSignal, directive };
