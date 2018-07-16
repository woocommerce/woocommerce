/** @format */

/**
 * External dependencies
 */
import ReactFlag from 'react-world-flags';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const Flag = ( { code, order, round, height, width, className } ) => {
	const classes = classnames( 'woocommerce-flag', className, {
		'is-round': round,
	} );

	let _code = code || 'unknown';
	if ( order && order.shipping ) {
		_code = order.shipping.country;
	} else if ( order && order.billing && order.billing.country ) {
		_code = order.billing.country;
	}

	const _height = round ? height * 2 : height;
	const _width = round ? width * 2 : width;
	const inlineStyles = round ? { height, width } : {};

	return (
		<div className={ classes } style={ inlineStyles }>
			<ReactFlag
				code={ _code }
				fallback={ <div className="woocommerce-flag__fallback" style={ inlineStyles } /> }
				height={ _height }
				width={ _width }
				alt=""
			/>
		</div>
	);
};

Flag.propTypes = {
	code: PropTypes.string,
	order: PropTypes.object,
	round: PropTypes.bool,
	height: PropTypes.number,
	width: PropTypes.number,
	className: PropTypes.string,
};

Flag.defaultProps = {
	height: 24,
	width: 24,
	round: true,
};

export default Flag;
