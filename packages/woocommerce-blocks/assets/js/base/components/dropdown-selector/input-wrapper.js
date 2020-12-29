const DropdownSelectorInputWrapper = ( { children, onClick } ) => {
	return (
		/* eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions */
		<div
			className="wc-block-dropdown-selector__input-wrapper wc-block-components-dropdown-selector__input-wrapper"
			onClick={ onClick }
		>
			{ children }
		</div>
	);
};

export default DropdownSelectorInputWrapper;
