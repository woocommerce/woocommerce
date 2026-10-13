/* Scenario context: Only the button is interactive; wrapper has no click/key handlers or conflicting styles. Label is translated upstream. Component is inside a checkout form, and button must remain non-submitting and keyboard operable. */
import { createElement } from '@wordpress/element';
export function CouponToggle( { expanded, onToggle, label } ) { return createElement( 'button', { type: 'button', 'aria-expanded': expanded, onClick: onToggle }, label ); }
