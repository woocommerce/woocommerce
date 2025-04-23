interface QuantityStepperProps {
	/**
	 * Whether the component is in site editor
	 */
	isSiteEditor?: boolean;
}

const QuantityStepper = ( { isSiteEditor }: QuantityStepperProps ) => {
	return (
		<div className="quantity wc-block-components-quantity-selector">
			<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">
				-
			</button>
			<input
				style={
					// In the post editor, the editor isn't in an iframe, so WordPress styles are applied. We need to remove them.
					! isSiteEditor
						? {
								backgroundColor: '#ffffff',
								lineHeight: 'normal',
								minHeight: 'unset',
								boxSizing: 'unset',
								borderRadius: 'unset',
						  }
						: {}
				}
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
