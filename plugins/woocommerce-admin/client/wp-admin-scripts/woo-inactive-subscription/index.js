/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { NavigationOptOutContainer } from './modal';
import './style.scss';

window.console.log( 'boom' );

const navigationOptOutRoot = document.createElement( 'div' );
navigationOptOutRoot.setAttribute( 'id', 'navigation-opt-out-root' );

render(
	<NavigationOptOutContainer />,
	document.body.appendChild( navigationOptOutRoot )
);
