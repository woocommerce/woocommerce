/* Customer note is a string. UI displays decoded text. */
import { createElement } from '@wordpress/element';
export function CustomerNote( { note } ) { return createElement( 'p', {}, note ); }
