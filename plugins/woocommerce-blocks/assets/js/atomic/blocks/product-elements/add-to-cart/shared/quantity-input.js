/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Quantity Input Component.
 *
 * @param {Object}         props          Incoming props for component
 * @param {boolean}        props.disabled Whether input is disabled or not.
 * @param {number}         props.min      Minimum value for input.
 * @param {number}         props.max      Maximum value for input.
 * @param {number}         props.value    Value for input.
 * @param {function():any} props.onChange Function to call on input change event.
 */
const QuantityInput = ( { disabled, min, max, value, onChange } ) => {
	return (
		<input
			className={ classnames(
				'wc-block-components-product-add-to-cart-quantity',
				{ hidden: max === 1 }
			) }
			type="number"
			value={ value }
			min={ min }
			max={ max }
			hidden={ max === 1 }
			disabled={ disabled }
			onChange={ ( e ) => {
				onChange( e.target.value );
			} }
		/>
	);
};

export default QuantityInput;
