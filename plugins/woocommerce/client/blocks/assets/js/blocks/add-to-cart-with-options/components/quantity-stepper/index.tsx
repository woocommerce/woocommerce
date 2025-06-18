const QuantityStepper = () => {
	return (
		<div className="quantity wc-block-components-quantity-selector">
			<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">
				-
			</button>
			<input
				type="number"
				value="1"
				className="input-text qty text"
				readOnly
			/>
			<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">
				+
			</button>
		</div>
	);
};

export default QuantityStepper;
