/**
 * Internal dependencies
 */
import { registerProductField } from '../registration';
import { TextAreaBlockEdit } from './field';

export function initTextAreaField() {
	return registerProductField( 'woocommerce/product-text-area-field', {
		id: 'summary',
		label: 'Summary',
		type: 'text',
		Edit: TextAreaBlockEdit,
	} );
}
