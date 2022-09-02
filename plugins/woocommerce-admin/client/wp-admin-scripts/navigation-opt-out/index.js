/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { NavigationOptOutContainer } from './container';
import './style.scss';

const navigationOptOutRoot = document.createElement( 'div' );
navigationOptOutRoot.setAttribute( 'id', 'navigation-opt-out-root' );

render(
	<NavigationOptOutContainer />,
	document.body.appendChild( navigationOptOutRoot )
);
