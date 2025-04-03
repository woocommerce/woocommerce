/**
 * Internal dependencies
 */
import { registerProductField } from '../registration';
import { ScheduleSaleFieldEdit } from './field';

export function initScheduleSaleField() {
	return registerProductField( 'woocommerce/product-schedule-sale-fields', {
		id: 'woocommerce/product-schedule-sale-fields',
		label: 'Schedule sale',
		type: 'text',
		Edit: ScheduleSaleFieldEdit,
	} );
}
