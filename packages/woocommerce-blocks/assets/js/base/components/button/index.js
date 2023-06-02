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

/** @typedef {import('react')} React */

/**
 * Component that visually renders a button but semantically might be `<button>` or `<a>` depending
 * on the props.
 *
 * @param {Object}              props             Incoming props for the component.
 * @param {string}              props.className   CSS classname used.
 * @param {boolean}             props.showSpinner Whether to show spinner or not.
 * @param {React.ReactChildren} props.children    Child components passed in.
 * @param {Object}              props.props       Rest of incoming props.
 */
const Button = ( { className, showSpinner = false, children, ...props } ) => {
	const buttonClassName = classNames(
		'wc-block-components-button',
		className,
		{
			'wc-block-components-button--loading': showSpinner,
		}
	);

	return (
		<WPButton className={ buttonClassName } { ...props }>
			{ showSpinner && (
				<span
					className="wc-block-components-button__spinner"
					aria-hidden="true"
				/>
			) }
			<span className="wc-block-components-button__text">
				{ children }
			</span>
		</WPButton>
	);
};

Button.propTypes = {
	className: PropTypes.string,
	showSpinner: PropTypes.bool,
	children: PropTypes.node,
};

export default Button;
