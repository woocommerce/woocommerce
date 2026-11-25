/* Customer note is a string. UI displays decoded text. */
import { createElement } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
export function CustomerNote( { note } ) { return createElement( 'p', {}, decodeEntities( note ) ); }
