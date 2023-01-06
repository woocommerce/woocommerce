/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Level } from './context';

type SectionProps = {
	/** The wrapper component for this section. Optional, defaults to `div`. If passed false, no wrapper is used. Additional props passed to Section are passed on to the component. */
	component?: React.ComponentType | string | false;
	/** Optional classname */
	className?: string;
	/** The children inside this section, rendered in the `component`. This increases the context level for the next heading used. */
	children: React.ReactNode;
};

/**
 * The section wrapper, used to indicate a sub-section (and change the header level context).
 */
export const Section: React.VFC< SectionProps > = ( {
	component,
	children,
	...props
} ) => {
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
};
