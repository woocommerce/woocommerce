/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component used to render a "chip" -- a list item containing some text.
 *
 * Each chip defaults to a list element but this can be customized by providing
 * a wrapperElement.
 */
const Chip = ( {
	text,
	screenReaderText = '',
	element = 'li',
	className = '',
	radius = 'small',
	children = null,
	...props
} ) => {
	const Wrapper = element;
	const wrapperClassName = classNames(
		className,
		'wc-block-components-chip',
		'wc-block-components-chip--radius-' + radius
	);

	const showScreenReaderText = Boolean(
		screenReaderText && screenReaderText !== text
	);

	return (
		// @ts-ignore
		<Wrapper className={ wrapperClassName } { ...props }>
			<span
				aria-hidden={ showScreenReaderText }
				className="wc-block-components-chip__text"
			>
				{ text }
			</span>
			{ showScreenReaderText && (
				<span className="screen-reader-text">{ screenReaderText }</span>
			) }
			{ children }
		</Wrapper>
	);
};

Chip.propTypes = {
	text: PropTypes.node.isRequired,
	screenReaderText: PropTypes.string,
	element: PropTypes.elementType,
	className: PropTypes.string,
	radius: PropTypes.oneOf( [ 'none', 'small', 'medium', 'large' ] ),
};

export default Chip;
