const QuantityStepper = () => {
	return (
		<div className="quantity wc-block-components-quantity-selector">
			<input
				type="number"
				value="1"
				className="input-text qty text"
				readOnly
			/>
			<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">
				−
			</button>
			<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">
				+
			</button>
		</div>
	);
};

export default QuantityStepper;
