/**
 * External dependencies
 */
import { createElement, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Level } from './context';

/**
 * These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels
 * (`h2`, `h3`, …) you can use `<H />` to create "section headings", which look to the parent `<Section />`s for the appropriate
 * heading level.
 *
 * @type {HTMLElement}
 */
export function H( props: React.HTMLAttributes< HTMLHeadingElement > ) {
	const level = useContext( Level );

	const Heading = 'h' + Math.min( level, 6 );
	return <Heading { ...props } />;
}
