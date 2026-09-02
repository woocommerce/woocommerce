export const COLOR_INPUT_SELECTOR =
	'input.wc-admin-visual-attribute-color-input';
export const IMAGE_INPUT_SELECTOR =
	'input.wc-admin-visual-attribute-image-input';

type InputValueChangeHandler = ( nextValue: string ) => void;

export const observeInputValueChanges = (
	input: HTMLInputElement,
	onValueChange: InputValueChangeHandler
) => {
	const inputPrototype = HTMLInputElement.prototype;
	const valueDescriptor = Object.getOwnPropertyDescriptor(
		inputPrototype,
		'value'
	);
	let hasValueOverride = false;

	if ( valueDescriptor?.get && valueDescriptor.set ) {
		Object.defineProperty( input, 'value', {
			...valueDescriptor,
			configurable: true,
			set( nextValue: string ) {
				valueDescriptor.set?.call( this, nextValue );
				onValueChange( nextValue );
			},
		} );
		hasValueOverride = true;
	}

	return () => {
		if ( hasValueOverride ) {
			delete ( input as { value?: string } ).value;
		}
	};
};

export const getSiblingVisualInput = (
	input: HTMLInputElement,
	selector: string
): HTMLInputElement | null => {
	const container =
		input.closest( 'form' ) ||
		input.closest( '.wc-add-attribute-term-fields' ) ||
		document;
	const sibling = container.querySelector( selector );

	return sibling instanceof HTMLInputElement ? sibling : null;
};

export const clearVisualInput = ( input: HTMLInputElement ) => {
	if ( ! input.value ) {
		return;
	}

	input.value = '';
	input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
};

export const clearSiblingVisualInput = (
	input: HTMLInputElement,
	selector: string
) => {
	const sibling = getSiblingVisualInput( input, selector );

	if ( sibling ) {
		clearVisualInput( sibling );
	}
};
