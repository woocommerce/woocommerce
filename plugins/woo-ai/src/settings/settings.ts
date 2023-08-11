/**
 * Internal dependencies
 */
import './settings.scss';

const fieldMap = {
	checkbox: document.getElementById(
		'woo_ai_enable_checkbox'
	) as HTMLInputElement,
	tone: document.getElementById(
		'woo_ai_tone_of_voice_select'
	) as HTMLSelectElement,
	describeBusiness: document.getElementById(
		'woo_ai_describe_store_description'
	) as HTMLInputElement,
};

( () => {
	if ( fieldMap.checkbox?.checked ) {
		jQuery( fieldMap.tone ).closest( 'tr' ).show();
		jQuery( fieldMap.describeBusiness ).closest( 'tr' ).show();
	} else {
		// This is a hack for Firefox support since it doesn't have :has() support yet.
		jQuery( fieldMap.tone ).closest( 'tr' ).hide();
		jQuery( fieldMap.describeBusiness ).closest( 'tr' ).hide();
	}

	fieldMap.checkbox?.addEventListener( 'change', ( { target } ) => {
		const checked = ( target as HTMLInputElement )?.checked;
		if ( checked ) {
			jQuery( fieldMap.tone ).closest( 'tr' ).fadeIn( 500 );
			jQuery( fieldMap.describeBusiness ).closest( 'tr' ).fadeIn( 500 );
		} else {
			jQuery( fieldMap.tone ).closest( 'tr' ).fadeOut( 500 );
			jQuery( fieldMap.describeBusiness ).closest( 'tr' ).fadeOut( 500 );
		}
	} );
} )();
