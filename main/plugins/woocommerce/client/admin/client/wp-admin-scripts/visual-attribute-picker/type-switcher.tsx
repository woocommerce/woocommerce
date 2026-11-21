/**
 * Internal dependencies
 */
import { IMAGE_INPUT_SELECTOR } from './utils';

const VISUAL_TYPE_RADIO_SELECTOR = 'input[name="wc_visual_attribute_type"]';
const COLOR_FIELD_WRAPPER_SELECTOR = '.wc-admin-visual-attribute-color';
const IMAGE_FIELD_WRAPPER_SELECTOR = '.wc-admin-visual-attribute-image';
const VISUAL_TYPE_COLOR = 'color';
const VISUAL_TYPE_IMAGE = 'image';

const hasStoredImage = ( input: HTMLInputElement ) => {
	const inputValue = input.value || input.getAttribute( 'value' ) || '';
	const imageId = Number.parseInt( inputValue, 10 );

	return imageId > 0;
};

const setVisualFieldVisibility = (
	container: ParentNode,
	visualType: typeof VISUAL_TYPE_COLOR | typeof VISUAL_TYPE_IMAGE
) => {
	const colorField = container.querySelector( COLOR_FIELD_WRAPPER_SELECTOR );
	const imageField = container.querySelector( IMAGE_FIELD_WRAPPER_SELECTOR );

	if ( colorField instanceof HTMLElement ) {
		colorField.hidden = visualType !== VISUAL_TYPE_COLOR;
	}

	if ( imageField instanceof HTMLElement ) {
		imageField.hidden = visualType !== VISUAL_TYPE_IMAGE;
	}
};

const mountVisualTypeSwitcher = ( visualTypeSwitcher: HTMLElement ) => {
	const container = visualTypeSwitcher.closest( 'form' );

	if ( ! container ) {
		return;
	}

	const imageInput = container.querySelector( IMAGE_INPUT_SELECTOR );
	const visualTypeRadios = Array.from(
		container.querySelectorAll< HTMLInputElement >(
			VISUAL_TYPE_RADIO_SELECTOR
		)
	);

	if (
		! ( imageInput instanceof HTMLInputElement ) ||
		visualTypeRadios.length === 0
	) {
		return;
	}

	const defaultVisualType = hasStoredImage( imageInput )
		? VISUAL_TYPE_IMAGE
		: VISUAL_TYPE_COLOR;
	visualTypeRadios.forEach( ( radio ) => {
		radio.checked = radio.value === defaultVisualType;
		radio.addEventListener( 'change', () => {
			const selectedVisualType =
				radio.value === VISUAL_TYPE_IMAGE
					? VISUAL_TYPE_IMAGE
					: VISUAL_TYPE_COLOR;

			if ( radio.checked ) {
				setVisualFieldVisibility( container, selectedVisualType );
			}
		} );
	} );

	setVisualFieldVisibility( container, defaultVisualType );
};

export const mountAllVisualTypeSwitchers = (
	context: ParentNode = document
) => {
	const visualTypeSwitchers = context.querySelectorAll(
		'.wc-admin-visual-attribute-type'
	);

	visualTypeSwitchers.forEach( ( visualTypeSwitcher ) => {
		if ( visualTypeSwitcher instanceof HTMLElement ) {
			mountVisualTypeSwitcher( visualTypeSwitcher );
		}
	} );
};
