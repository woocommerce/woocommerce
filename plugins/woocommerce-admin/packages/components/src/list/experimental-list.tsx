/**
 * External dependencies
 */
import { Children, cloneElement, isValidElement } from '@wordpress/element';
import { CSSTransition, TransitionGroup } from 'react-transition-group';

/**
 * Internal dependencies
 */
import type { ListAnimation } from './experimental-list-item';

type ListType = 'ol' | 'ul';

type ListProps = {
	listType?: ListType;
	animation?: ListAnimation;
} & React.HTMLAttributes< HTMLElement >;

export const ExperimentalList: React.FC< ListProps > = ( {
	children,
	listType = 'ul',
	animation = 'none',
	// Allow passing any other property overrides that are legal on an HTML element
	...otherProps
} ) => {
	return (
		<TransitionGroup
			component={ listType }
			className="woocommerce-list"
			{ ...otherProps }
		>
			{ /* Wrapping all children in a CSS Transition means no invalid props are passed to children and that anything can be animated. */ }
			{
				Children.map( children, ( child ) => {
					if ( isValidElement( child ) ) {
						const {
							onExited,
							in: inTransition,
							enter,
							exit,
							...remainingProps
						} = child.props;
						const animationProp =
							remainingProps.animation || animation;
						return (
							<CSSTransition
								timeout={ 500 }
								onExited={ onExited }
								in={ inTransition }
								enter={ enter }
								exit={ exit }
								classNames="woocommerce-list__item"
							>
								{ cloneElement( child, {
									animation: animationProp,
									...remainingProps,
								} ) }
							</CSSTransition>
						);
					}

					return child;
					// TODO - create a less restrictive type definition for children of react-transition-group. React.Children.map seems incompatible with the type expected by `children`.
				} ) as React.ReactElement[]
			}
		</TransitionGroup>
	);
};
