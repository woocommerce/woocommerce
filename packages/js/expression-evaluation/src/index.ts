/**
 * Internal dependencies
 */

import { parser } from './parser';

export function evaluate( expression: string, context = {} ) {
	return parser.parse( expression, { context } );
}
