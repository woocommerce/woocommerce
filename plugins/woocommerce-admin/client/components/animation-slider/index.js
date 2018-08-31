/** @format */
/**
 * External dependencies
 */
import { Component, createRef } from '@wordpress/element';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import './style.scss';

/**
 * This component creates slideable content controlled by an animate prop to direct the contents to slide left or right.
 * All other props are passed to `CSSTransition`. More info at http://reactcommunity.org/react-transition-group/css-transition
 */
class AnimationSlider extends Component {
	constructor() {
		super();
		this.state = {
			animate: null,
		};
		this.container = createRef();
		this.onExited = this.onExited.bind( this );
	}

	onExited() {
		const { onExited, focusOnChange } = this.props;
		if ( onExited ) {
			onExited();
		}
		if ( focusOnChange ) {
			const focusable = this.container.current.querySelector(
				'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
			);
			if ( focusable ) {
				focusable.focus();
			}
		}
	}

	render() {
		const { children, animationKey, animate } = this.props;
		const containerClasses = classnames(
			'woocommerce-slide-animation',
			animate && `animate-${ animate }`
		);
		return (
			<div className={ containerClasses } ref={ this.container }>
				<TransitionGroup>
					<CSSTransition
						timeout={ 200 }
						classNames="slide"
						key={ animationKey }
						{ ...this.props }
						onExited={ this.onExited }
					>
						{ status => children( { status } ) }
					</CSSTransition>
				</TransitionGroup>
			</div>
		);
	}
}

AnimationSlider.propTypes = {
	/**
	 * A function returning rendered content with argument status, reflecting `CSSTransition` status.
	 */
	children: PropTypes.func.isRequired,
	/**
	 * A unique identifier for each slideable page.
	 */
	animationKey: PropTypes.any.isRequired,
	/**
	 * null, 'left', 'right', to designate which direction to slide on a change.
	 */
	animate: PropTypes.oneOf( [ null, 'left', 'right' ] ),
	/**
	 * When set to true, the first focusable element will be focused after an animation has finished.
	 */
	focusOnChange: PropTypes.bool,
};

export default AnimationSlider;
