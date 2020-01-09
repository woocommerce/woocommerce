/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

const QuantitySelector = ( {
	className,
	quantity = 1,
	onChange = () => null,
} ) => {
	const classes = classNames(
		'wc-block-quantity-selector__input',
		className
	);

	// For now just use a regular number edit. (temporary)
	// @todo https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/1521
	return (
		<input
			className={ classes }
			type="number"
			step="1"
			min="0"
			value={ quantity }
			onChange={ ( event ) => onChange( event.target.value ) }
		/>
	);
};

QuantitySelector.propTypes = {
	className: PropTypes.string,
	quantity: PropTypes.number,
	onChange: PropTypes.func,
};

export default QuantitySelector;
