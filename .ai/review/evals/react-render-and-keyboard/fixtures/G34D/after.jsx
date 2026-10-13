/* Coupon toggle is inside a checkout form. Its translated label and onToggle handler are provided by the parent. */
import { createElement } from '@wordpress/element';
export function CouponToggle( { expanded, onToggle, label } ) { return createElement( 'div', { className: 'wc-block-checkout__coupon-toggle' }, createElement( 'button', { type: 'button', 'aria-expanded': expanded, onClick: onToggle }, label ) ); }
