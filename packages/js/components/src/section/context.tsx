/**
 * External dependencies
 */
import { createContext } from '@wordpress/element';

/**
 * Context container for heading level. We start at 2 because the `h1` is defined in <Header />
 *
 * See https://medium.com/@Heydon/managing-heading-levels-in-design-systems-18be9a746fa3
 */
const Level: React.Context< number > = createContext( 2 );

export { Level };
