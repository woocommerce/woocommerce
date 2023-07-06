/**
 * Internal dependencies
 */
import './settings.scss';

const fieldMap = {
	enabled: document.getElementById( 'woo_ai_enable_checkbox' ),
	tone: document.getElementById( 'woo_ai_tone_of_voice_select' ),
	describeBusiness: document.getElementById(
		'ai_describe_store_description'
	),
};

const getSelectedOption = () =>
	document.querySelector( `.woo-ai-settings-tone-option.selected` );

const setSelectedDescription = ( value ) => {
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

const setDisabledState = ( disabled ) => {
	fieldMap.tone.disabled = disabled;
	fieldMap.describeBusiness.disabled = disabled;
};

( () => {
	if ( fieldMap.enabled.checked ) {
		setSelectedDescription( fieldMap.tone.value );
		setDisabledState( false );
	}

	fieldMap.enabled.addEventListener(
		'change',
		( { target: { checked } } ) => {
			if ( checked ) {
				setSelectedDescription( fieldMap.tone.value );
			} else {
				const selected = getSelectedOption();

				if ( selected ) {
					selected.classList.remove( 'selected' );
				}
			}
			setDisabledState( ! checked );
		}
	);

	fieldMap.tone.addEventListener( 'change', ( { target: { value } } ) =>
		setSelectedDescription( value )
	);
} )();
