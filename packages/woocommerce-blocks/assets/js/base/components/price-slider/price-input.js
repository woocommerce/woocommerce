/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { Fragment } from '@wordpress/element';

const PriceInput = ( { disabled, onBlur, onChange, minPrice, maxPrice } ) => {
	return (
		<Fragment>
			<input
				type="text"
				size="5"
				className="wc-block-price-filter__amount wc-block-price-filter__amount--min wc-block-form-text-input"
				aria-label={ __(
					'Filter products by minimum price',
					'woocommerce'
				) }
				onChange={ onChange }
				onBlur={ onBlur }
				disabled={ disabled }
				value={ minPrice }
			/>
			<input
				type="text"
				size="5"
				className="wc-block-price-filter__amount wc-block-price-filter__amount--max wc-block-form-text-input"
				aria-label={ __(
					'Filter products by maximum price',
					'woocommerce'
				) }
				onChange={ onChange }
				onBlur={ onBlur }
				disabled={ disabled }
				value={ maxPrice }
			/>
		</Fragment>
	);
};

PriceInput.propTypes = {
	/**
	 * Is the text input disabled?
	 */
	disabled: PropTypes.bool,
	/**
	 * Callback fired on input.
	 */
	onBlur: PropTypes.func,
	/**
	 * Callback fired on input.
	 */
	onChange: PropTypes.func,
	/**
	 * Min price to display. This is a string because it contains currency e.g. $10.00.
	 */
	minPrice: PropTypes.string.isRequired,
	/**
	 * Max price to display. This is a string because it contains currency e.g. $10.00.
	 */
	maxPrice: PropTypes.string.isRequired,
};

PriceInput.defaultProps = {
	disabled: false,
	onBlur: () => {},
	onChange: () => {},
};

export default PriceInput;
