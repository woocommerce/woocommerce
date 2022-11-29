/**
 * Internal dependencies
 */
import { addCustomerEffortScoreExitPageListener } from '~/customer-effort-score-tracks/customer-effort-score-exit-page';
import { staticFormDataToObject } from '~/utils/static-form-helper';

type FormElements = {
	post?: HTMLFormElement;
} & HTMLCollectionOf< HTMLFormElement >;
const forms: FormElements = document.forms;
if ( forms && forms.post ) {
	let triggeredSaveOrDeleteButton = false;
	const saveButton = document.querySelector( '.save_order' );
	const deleteButton = document.querySelector( '.submitdelete' );

	if ( saveButton ) {
		saveButton.addEventListener( 'click', () => {
			triggeredSaveOrDeleteButton = true;
		} );
	}
	if ( deleteButton ) {
		deleteButton.addEventListener( 'click', () => {
			triggeredSaveOrDeleteButton = true;
		} );
	}
	const formData = staticFormDataToObject( forms.post );
	addCustomerEffortScoreExitPageListener( 'shop_order_update', () => {
		if ( triggeredSaveOrDeleteButton ) {
			return false;
		}
		const newFormData = forms.post
			? staticFormDataToObject( forms.post )
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
