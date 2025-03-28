/**
 * Internal dependencies
 */
import { registerProductField } from '../registration';
import { NameBlockEdit } from './field';

export function initNameField() {
	return registerProductField( 'woocommerce/product-name-field', {
		id: 'name',
		label: 'Name',
		type: 'text',
		Edit: NameBlockEdit,
	} );
}
