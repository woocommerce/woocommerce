/**
 * Internal dependencies
 */
import { addCustomerEffortScoreExitPageListener } from '~/customer-effort-score-tracks/customer-effort-score-exit-page';
import { staticFormDataToObject } from '~/utils/static-form-helper';

type FormElements = {
	mainform?: HTMLFormElement;
} & HTMLCollectionOf< HTMLFormElement >;
const forms: FormElements = document.forms;
if ( forms && forms.mainform ) {
	const formData = staticFormDataToObject( forms.mainform );
	addCustomerEffortScoreExitPageListener( 'settings_change', () => {
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
