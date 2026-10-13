/* Scenario context: Checkout coupon toggle receives a translated accessible label. It expands the only coupon-entry panel. No delegated key handler exists; onToggle is the only action handler. Browser buttons activate on Enter/Space, but a focusable div does not. */
import { createElement } from '@wordpress/element';
export function CouponToggle( { expanded, onToggle, label } ) { return createElement( 'div', { tabIndex: 0, 'aria-expanded': expanded, onClick: onToggle }, label ); }
