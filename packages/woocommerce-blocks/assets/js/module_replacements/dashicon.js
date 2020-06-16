/**
 * External dependencies
 */
import {
	arrowDownAlt2 as ArrowDownIcon,
	noAlt as DismissIcon,
} from '@woocommerce/icons';
import { createElement } from '@wordpress/element';

// Note: Aside from import/export, everything in this file must be IE11 friendly
// because currently it does not go through babel transpiling. It is injected
// as a replacement for the `@wordpress/component/dashicon` component via
// the Webpack NormalModuleReplacementPlugin plugin.

export default function( props ) {
	let Icon = function() {
		return null;
	};
	switch ( props.icon ) {
		case 'arrow-down-alt2':
			Icon = ArrowDownIcon;
			break;
		case 'no-alt':
			Icon = DismissIcon;
			break;
	}
	return createElement( Icon, {
		size: props.size || 20,
		className: props.className,
	} );
}
