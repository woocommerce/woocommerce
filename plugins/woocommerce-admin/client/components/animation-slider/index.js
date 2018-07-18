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
	children: PropTypes.func.isRequired,
	animationKey: PropTypes.any.isRequired,
	animate: PropTypes.oneOf( [ null, 'left', 'right' ] ),
	focusOnChange: PropTypes.bool,
};

export default AnimationSlider;
