const DropdownSelectorInput = ( {
	checked,
	getInputProps,
	inputRef,
	isDisabled,
	onFocus,
	onRemoveItem,
	placeholder,
	value,
} ) => {
	return (
		<input
			{ ...getInputProps( {
				ref: inputRef,
				className: 'wc-block-dropdown-selector__input',
				disabled: isDisabled,
				onFocus,
				onKeyDown( e ) {
					if (
						e.key === 'Backspace' &&
						! value &&
						checked.length > 0
					) {
						onRemoveItem( checked[ checked.length - 1 ] );
					}
				},
				placeholder,
			} ) }
		/>
	);
};

export default DropdownSelectorInput;
