/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Context container for heading level. We start at 2 because the `h1` is defined in <Header />
 *
 * See https://medium.com/@Heydon/managing-heading-levels-in-design-systems-18be9a746fa3
 */
const Level = createContext( 2 );

/**
 * These components are used to frame out the page content for accessible heading hierarchy. Instead of defining fixed heading levels
 * (`h2`, `h3`, …) you can use `<H />` to create "section headings", which look to the parent `<Section />`s for the appropriate
 * heading level.
 *
 * @param {Object} props -
 * @return {Object} -
 */
export function H( props ) {
	return (
		<Level.Consumer>
			{ ( level ) => {
				const Heading = 'h' + Math.min( level, 6 );
				return <Heading { ...props } />;
			} }
		</Level.Consumer>
	);
}

/**
 * The section wrapper, used to indicate a sub-section (and change the header level context).
 *
 * @param root0
 * @param root0.component
 * @param root0.children
 * @return {Object} -
 */
export function Section( { component, children, ...props } ) {
	const Component = component || 'div';
	return (
		<Level.Consumer>
			{ ( level ) => (
				<Level.Provider value={ level + 1 }>
					{ component === false ? (
						children
					) : (
						<Component { ...props }>{ children }</Component>
					) }
				</Level.Provider>
			) }
		</Level.Consumer>
	);
}

Section.propTypes = {
	/**
	 * The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props
	 * passed to Section are passed on to the component.
	 */
	component: PropTypes.oneOfType( [
		PropTypes.func,
		PropTypes.string,
		PropTypes.bool,
	] ),
	/**
	 * The children inside this section, rendered in the `component`. This increases the context level for the next heading used.
	 */
	children: PropTypes.node,
};
