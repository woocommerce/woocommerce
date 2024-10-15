/**
 * External dependencies
 */
import { useRef, useState, useEffect } from '@wordpress/element';
import clsx from 'clsx';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import PropTypes from 'prop-types';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import './style.scss';

const Slider = ( { children, animationKey, animate } ) => {
	const [ height, updateHeight ] = useState( null );

	const container = useRef();

	const containerClasses = clsx(
		'woocommerce-marketing-slider',
		animate && `animate-${ animate }`
	);

	const style = {};

	if ( height ) {
		style.height = height;
	}

	// timeout should be slightly longer than the CSS animation
	const timeout = 320;

	const updateSliderHeight = () => {
		const slide = container.current.querySelector(
			'.woocommerce-marketing-slider__slide'
		);
		updateHeight( slide.clientHeight );
	};

	const debouncedUpdateSliderHeight = debounce( updateSliderHeight, 50 );

	useEffect( () => {
		// Update the slider height on Resize
		window.addEventListener( 'resize', debouncedUpdateSliderHeight );
		return () => {
			window.removeEventListener( 'resize', debouncedUpdateSliderHeight );
		};
	}, [] );

	/**
	 * Fix slider height before a slide enters because slides are absolutely position
	 */
	const onEnter = () => {
		const newSlide = container.current.querySelector( '.slide-enter' );
		updateHeight( newSlide.clientHeight );
	};

	return (
		<div className={ containerClasses } ref={ container } style={ style }>
			<TransitionGroup>
				<CSSTransition
					timeout={ timeout }
					classNames="slide"
					key={ animationKey }
					onEnter={ onEnter }
				>
					<div className="woocommerce-marketing-slider__slide">
						{ children }
					</div>
				</CSSTransition>
			</TransitionGroup>
		</div>
	);
};

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
