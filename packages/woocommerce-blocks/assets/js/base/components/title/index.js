/**
 * External dependencies
 */
import classNames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

/** @typedef {import('react')} React */

/**
 * Component that renders a block title.
 *
 * @param {Object} props Incoming props for the component.
 * @param {React.ReactNode} [props.children] Children elements this component wraps.
 * @param {string} [props.className] CSS class used.
 * @param {string} props.headingLevel Heading level for title.
 * @param {Object} [props.props] Rest of props passed through to component.
 */
const Title = ( { children, className, headingLevel, ...props } ) => {
	const buttonClassName = classNames(
		'wc-block-components-title',
		className
	);
	const TagName = `h${ headingLevel }`;

	return (
		<TagName className={ buttonClassName } { ...props }>
			{ children }
		</TagName>
	);
};

Title.propTypes = {
	headingLevel: PropTypes.oneOf( [ '1', '2', '3', '4', '5', '6' ] )
		.isRequired,
	className: PropTypes.string,
	children: PropTypes.node,
};

export default Title;
