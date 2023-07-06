/**
 * Internal dependencies
 */
import './settings.scss';

interface FieldMap {
	enabled: HTMLInputElement | null;
	tone: HTMLSelectElement | null;
	describeBusiness: HTMLInputElement | null;
}

const fieldMap: FieldMap = {
	enabled: document.getElementById(
		'woo_ai_enable_checkbox'
	) as HTMLInputElement,
	tone: document.getElementById(
		'woo_ai_tone_of_voice_select'
	) as HTMLSelectElement,
	describeBusiness: document.getElementById(
		'ai_describe_store_description'
	) as HTMLInputElement,
};

const getSelectedOption = (): Element | null =>
	document.querySelector( `.woo-ai-settings-tone-option.selected` );

const setSelectedDescription = ( value: string ): void => {
	const selected = document.querySelector(
		`.woo-ai-settings-tone-option[data-option="${ value }"]`
	);

	if ( ! selected ) {
		return;
	}

	const previousSelected = getSelectedOption();

	if ( previousSelected ) {
		previousSelected.classList.remove( 'selected' );
	}

	selected.classList.add( 'selected' );
};

const setDisabledState = ( disabled: boolean ): void => {
	if ( fieldMap.tone && fieldMap.describeBusiness ) {
		fieldMap.tone.disabled = disabled;
		fieldMap.describeBusiness.disabled = disabled;
	}
};

( () => {
	if ( fieldMap.enabled?.checked ) {
		setSelectedDescription( fieldMap.tone?.value || '' );
		setDisabledState( false );
	}

	fieldMap.enabled?.addEventListener( 'change', ( { target } ) => {
		const checked = ( target as HTMLInputElement )?.checked;
		if ( checked ) {
			setSelectedDescription( fieldMap.tone?.value || '' );
		} else {
			const selected = getSelectedOption();

			if ( selected ) {
				selected.classList.remove( 'selected' );
			}
		}
		setDisabledState( ! checked );
	} );

	fieldMap.tone?.addEventListener( 'change', ( { target } ) => {
		const value = ( target as HTMLSelectElement )?.value;
		setSelectedDescription( value );
	} );
} )();
