/**
 * Helper function to move all form elements to an object.
 */
export function staticFormDataToObject( elForm: HTMLFormElement ) {
	const formFields = elForm.querySelectorAll<
		HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
	>( 'input, select, textarea' );
	const values: Record< string, string | boolean | number | string[] > = {};
	for ( const field of formFields ) {
		const sKey = field.name || field.id;
		if (
			field.type === 'button' ||
			field.type === 'image' ||
			field.type === 'submit' ||
			field.type === 'hidden' ||
			! sKey
		)
			continue;
		switch ( field.type ) {
			case 'checkbox':
				values[ sKey ] = +( field as HTMLInputElement ).checked;
				break;
			case 'radio':
				if ( values[ sKey ] === undefined ) {
					values[ sKey ] = '';
				}
				if ( ( field as HTMLInputElement ).checked ) {
					values[ sKey ] = field.value;
				}
				break;
			case 'select-multiple':
				const options = [];
				for ( const option of ( field as HTMLSelectElement ).options ) {
					if ( option.selected ) {
						options.push( option.value );
					}
				}
				values[ sKey ] = options;
				break;
			default:
				values[ sKey ] = field.value;
		}
	}
	return values;
}
