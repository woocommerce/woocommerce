/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Level } from './context';

type SectionProps = {
	component?: React.ComponentType | string | boolean;
	className?: string;
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
						// @ts-expect-error: Raises JSX component `Component` doesn't have constructor to call error.
						// Suppressing the error since this component accepts func, string, and bool
						// I wasn't able to find to correct type.
						<Component { ...props }>{ children }</Component>
					) }
				</Level.Provider>
			) }
		</Level.Consumer>
	);
};

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
	/**
	 * Optional classname
	 */
	className: PropTypes.string,
};
