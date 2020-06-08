/**
 * External dependencies
 */
import {
	arrowDownAlt2 as ArrowDownIcon,
	noAlt as DismissIcon,
} from '@woocommerce/icons';
import { createElement } from '@wordpress/element';

export default ( { icon, size = 20, className, ...extraProps } ) => {
	let Icon = () => null;
	switch ( icon ) {
		case 'arrow-down-alt2':
			Icon = ArrowDownIcon;
			break;
		case 'no-alt':
			Icon = DismissIcon;
			break;
	}
	// can't use JSX here because the Webpack NormalModuleReplacementPlugin
	// is unable to parse JSX at that point in the build.
	return createElement( Icon, { size, className, ...extraProps } );
};
