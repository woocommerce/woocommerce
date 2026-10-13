/* Payment method IDs are stable. Extensions may change their order during a checkout session. */
import { createElement, useState } from '@wordpress/element';
function PaymentRow( { method } ) { const [ note, setNote ] = useState( '' ); return createElement( 'label', {}, method.label, createElement( 'input', { value: note, onChange: ( event ) => setNote( event.target.value ) } ) ); }
export function PaymentList( { methods } ) { return createElement( 'div', {}, methods.map( ( method, index ) => createElement( PaymentRow, { key: index, method } ) ) ); }
