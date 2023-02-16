/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CategoryDropdownContainer } from './container';
import './style.scss';

const navigationOptOutRoot = document.createElement( 'div' );
navigationOptOutRoot.setAttribute( 'id', 'navigation-opt-out-root' );

const container = document.querySelector( '.wc-select-tree-control' );
render(
	<CategoryDropdownContainer />,
	container.appendChild( navigationOptOutRoot )
);
