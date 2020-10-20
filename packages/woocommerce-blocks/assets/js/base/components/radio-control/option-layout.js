const OptionLayout = ( {
	label,
	secondaryLabel,
	description,
	secondaryDescription,
	id,
} ) => {
	return (
		<div className="wc-block-components-radio-control__option-layout">
			<div className="wc-block-components-radio-control__label-group">
				{ label && (
					<span
						id={ id ? `${ id }__label` : null }
						className="wc-block-components-radio-control__label"
					>
						{ label }
					</span>
				) }
				{ secondaryLabel && (
					<span
						id={ id ? `${ id }__secondary-label` : null }
						className="wc-block-components-radio-control__secondary-label"
					>
						{ secondaryLabel }
					</span>
				) }
			</div>
			<div className="wc-block-components-radio-control__description-group">
				{ description && (
					<span
						id={ id ? `${ id }__description` : null }
						className="wc-block-components-radio-control__description"
					>
						{ description }
					</span>
				) }
				{ secondaryDescription && (
					<span
						id={ id ? `${ id }__secondary-description` : null }
						className="wc-block-components-radio-control__secondary-description"
					>
						{ secondaryDescription }
					</span>
				) }
			</div>
		</div>
	);
};

export default OptionLayout;
