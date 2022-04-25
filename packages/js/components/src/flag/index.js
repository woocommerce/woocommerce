/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import emojiFlags from 'emoji-flags';
import { get } from 'lodash';
import { createElement } from '@wordpress/element';

/**
 * Use the `Flag` component to display a country's flag using the operating system's emojis.
 *
 * @param {Object}  props
 * @param {string}  props.code
 * @param {Object}  props.order
 * @param {string}  props.className
 * @param {string}  props.size
 * @param {boolean} props.hideFromScreenReader
 * @return {Object} - React component.
 */
const Flag = ( { code, order, className, size, hideFromScreenReader } ) => {
	const classes = classnames( 'woocommerce-flag', className );

	let _code = code || 'unknown';
	if ( order && order.shipping && order.shipping.country ) {
		_code = order.shipping.country;
	} else if ( order && order.billing && order.billing.country ) {
		_code = order.billing.country;
	}

	const inlineStyles = {
		fontSize: size,
	};

	const emoji = get( emojiFlags.countryCode( _code ), 'emoji' );

	return (
		<div
			className={ classes }
			style={ inlineStyles }
			aria-hidden={ hideFromScreenReader }
		>
			{ emoji && <span>{ emoji }</span> }
			{ ! emoji && (
				<span className="woocommerce-flag__fallback">
					Invalid country flag
				</span>
			) }
		</div>
	);
};

Flag.propTypes = {
	/**
	 * Two letter, three letter or three digit country code.
	 */
	code: PropTypes.string,
	/**
	 * An order can be passed instead of `code` and the code will automatically be pulled from the billing or shipping data.
	 */
	order: PropTypes.object,
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * Supply a font size to be applied to the emoji flag.
	 */
	size: PropTypes.number,
};

export default Flag;
