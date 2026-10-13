/* Fixed translated checkout legend, created once at module initialization. */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
const labels = Object.freeze( [ __( 'Required', 'woocommerce' ), __( 'Optional', 'woocommerce' ) ] );
export function CheckoutLegend() { return createElement( 'ul', {}, labels.map( ( label, index ) => createElement( 'li', { key: label }, label ) ) ); }
