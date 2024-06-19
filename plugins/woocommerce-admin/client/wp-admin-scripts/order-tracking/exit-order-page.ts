/**
 * External dependencies
 */
import { addCustomerEffortScoreExitPageListener } from '@woocommerce/customer-effort-score';

/**
 * Internal dependencies
 */
import { staticFormDataToObject } from '~/utils/static-form-helper';

type FormElements = {
	post?: HTMLFormElement;
	order?: HTMLFormElement;
} & HTMLCollectionOf< HTMLFormElement >;
const forms: FormElements = document.forms;
if ( forms?.post || forms?.order ) {
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
	const formData = staticFormDataToObject(
		( forms?.post || forms?.order ) as HTMLFormElement
	);
	addCustomerEffortScoreExitPageListener( 'shop_order_update', () => {
		if ( triggeredSaveOrDeleteButton ) {
			return false;
		}
		const newFormData =
			forms?.post || forms?.order
				? staticFormDataToObject(
						( forms?.post || forms?.order ) as HTMLFormElement
				  )
				: {};
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
				return true;
			}
		}
		return false;
	} );
}
