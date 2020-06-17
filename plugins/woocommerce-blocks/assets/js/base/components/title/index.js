/**
 * External dependencies
 */
import classNames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component that renders a block title.
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
