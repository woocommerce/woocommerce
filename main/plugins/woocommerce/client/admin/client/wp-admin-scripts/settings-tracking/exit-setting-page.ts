/**
 * External dependencies
 */
import { addCustomerEffortScoreExitPageListener } from '@woocommerce/customer-effort-score';

/**
 * Internal dependencies
 */
import { staticFormDataToObject } from '~/utils/static-form-helper';

type FormElements = {
	mainform?: HTMLFormElement;
} & HTMLCollectionOf< HTMLFormElement >;
const forms: FormElements = document.forms;
if ( forms && forms.mainform ) {
	let triggeredSaveButton = false;
	const saveButton = document.querySelector( '.woocommerce-save-button' );

	if ( saveButton ) {
		saveButton.addEventListener( 'click', () => {
			triggeredSaveButton = true;
		} );
	}
	const formData = staticFormDataToObject( forms.mainform );
	addCustomerEffortScoreExitPageListener( 'settings_change', () => {
		if ( triggeredSaveButton ) {
			return false;
		}
		const newFormData = forms.mainform
			? staticFormDataToObject( forms.mainform )
			: {};
		let isDirty = false;
		for ( const key of Object.keys( formData ) ) {
			const value =
				typeof formData[ key ] === 'object'
					? JSON.stringify( formData[ key ] )
					: formData[ key ];
			const newValue =
				typeof newFormData[ key ] === 'object'
					? JSON.stringify( newFormData[ key ] )
					: newFormData[ key ];
			if ( value !== newValue ) {
				isDirty = true;
				break;
			}
		}
		return isDirty;
	} );
}
