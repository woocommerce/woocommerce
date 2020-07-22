/**
 * Quantity Input Component.
 */
const QuantityInput = ( { disabled, min, max, value, onChange } ) => {
	return (
		<input
			className="wc-block-components-product-add-to-cart-quantity"
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
