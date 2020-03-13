/**
 * External dependencies
 */
import { Button as WPButton } from 'wordpress-components';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Component that visually renders a button but semantically might be `<button>` or `<a>` depending on the props.
 */
const Button = ( { className, ...props } ) => {
	return (
		<WPButton
			className={ classNames( 'button', 'wc-block-button', className ) }
			{ ...props }
		/>
	);
};

Button.propTypes = {
	className: PropTypes.string,
};

export default Button;
