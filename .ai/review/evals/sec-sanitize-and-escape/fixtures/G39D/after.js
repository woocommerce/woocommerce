/* Scenario context: Customer-controlled note is a string. UI deliberately changes from displaying HTML entity text to decoded plain text. Standard React renderer treats child strings as text; no RawHTML, dangerouslySetInnerHTML, or downstream innerHTML is used. */
import { createElement } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
export function CustomerNote( { note } ) { return createElement( 'p', {}, decodeEntities( note ) ); }
