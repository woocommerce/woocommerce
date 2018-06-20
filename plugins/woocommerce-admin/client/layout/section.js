/** @format */
/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

/**
 * Context container for heading level. We start at 2 because the `h1` is defined in <Header />
 *
 * See https://medium.com/@Heydon/managing-heading-levels-in-design-systems-18be9a746fa3
 */
const Level = createContext( 2 );

export function Section( { component, children, ...props } ) {
	const Component = component || 'div';
	return (
		<Level.Consumer>
			{ level => (
				<Level.Provider value={ level + 1 }>
					{ false === component ? children : <Component { ...props }>{ children }</Component> }
				</Level.Provider>
			) }
		</Level.Consumer>
	);
}

export function H( props ) {
	return (
		<Level.Consumer>
			{ level => {
				const Heading = 'h' + Math.min( level, 6 );
				return <Heading { ...props } />;
			} }
		</Level.Consumer>
	);
}
