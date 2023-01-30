/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

/** @typedef {import('react')} React */

/**
 * Component used to render a "chip" -- a list item containing some text.
 *
 * Each chip defaults to a list element but this can be customized by providing
 * a wrapperElement.
 *
 * @param {Object} props Incoming props for the component.
 * @param {string} props.text Text for chip content.
 * @param {string} props.screenReaderText Screenreader text for the content.
 * @param {string} props.element The element type for the chip.
 * @param {string} props.className CSS class used.
 * @param {string} props.radius Radius size.
 * @param {React.ReactChildren|null} props.children React children.
 * @param {Object} props.props Rest of props passed through to component.
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
