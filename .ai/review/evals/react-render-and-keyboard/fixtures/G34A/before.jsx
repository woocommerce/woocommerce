/* Scenario context: Payment methods have unique stable IDs. Extensions may reorder them when availability changes. Each row keeps shopper-entered note in local state. Reordering must keep notes attached to the same payment method. */
import { createElement, useState } from '@wordpress/element';
function PaymentRow( { method } ) { const [ note, setNote ] = useState( '' ); return createElement( 'label', {}, method.label, createElement( 'input', { value: note, onChange: ( event ) => setNote( event.target.value ) } ) ); }
export function PaymentList( { methods } ) { return createElement( 'div', {}, methods.map( ( method, index ) => createElement( PaymentRow, { key: method.id, method } ) ) ); }
