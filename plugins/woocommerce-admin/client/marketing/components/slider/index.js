/**
 * External dependencies
 */
import { Component, createRef } from '@wordpress/element';
import classnames from 'classnames';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import PropTypes from 'prop-types';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss'

class Slider extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			height: null,
		};
		this.container = createRef();
		this.onEnter = this.onEnter.bind( this );
		this.updateSliderHeight = this.updateSliderHeight.bind( this );
	}

	/**
	 * Update the slider height on Resize
	 */
	componentDidMount() {
		// Update the slider height on Resize
		window.addEventListener(
			'resize',
			debounce( this.updateSliderHeight, 50 )
		);
	}

	componentWillUnmount() {
		window.removeEventListener( 'resize', this.updateSliderHeight )
	}

	updateSliderHeight() {
		const slide = this.container.current.querySelector( '.woocommerce-marketing-slider__slide' );
		this.setState( { height: slide.clientHeight } );
	}

	/**
	 * Fix slider height before a slide enters because slides are absolutely position
	 */
	onEnter() {
		const newSlide = this.container.current.querySelector( '.slide-enter' );
		this.setState( { height: newSlide.clientHeight } );
	}

	render() {
		const { children, animationKey, animate } = this.props;
		const { height } = this.state;
		const containerClasses = classnames(
			'woocommerce-marketing-slider',
			animate && `animate-${ animate }`
		);
		const style = {};
		if ( height ) {
			style.height = height;
		}

		return (
			<div className={ containerClasses }
				ref={ this.container }
				style={ style }>
				<TransitionGroup>
					<CSSTransition
						// timeout should be slightly longer than the CSS animation
						timeout={ 320 }
						classNames="slide"
						key={ animationKey }
						onEnter={ this.onEnter }
					>
						<div className="woocommerce-marketing-slider__slide">{ children }</div>
					</CSSTransition>
				</TransitionGroup>
			</div>
		);
	}
}

Slider.propTypes = {
	/**
	 * A unique identifier for each slideable page.
	 */
	animationKey: PropTypes.any.isRequired,
	/**
	 * null, 'left', 'right', to designate which direction to slide on a change.
	 */
	animate: PropTypes.oneOf( [ null, 'left', 'right' ] ),
};

export default Slider;
