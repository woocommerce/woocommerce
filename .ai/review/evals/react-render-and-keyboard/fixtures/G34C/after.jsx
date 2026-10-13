/* Scenario context: labels is a fixed two-item translated legend created once per full page load. Entries never reorder, insert, remove, or change during component lifetime. Rows are plain li text nodes without inputs, local state, effects, or refs. */
import { createElement } from '@wordpress/element';
export function CheckoutLegend( { labels } ) { return createElement( 'ul', {}, labels.map( ( label, index ) => createElement( 'li', { key: index }, label ) ) ); }
