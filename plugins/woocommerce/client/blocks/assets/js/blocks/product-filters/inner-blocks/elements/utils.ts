/**
 * Internal dependencies
 */
import { ELEMENT_KEYS, type ElementKey } from './types';

export function detectElement( value: string ): ElementKey | undefined {
	const needle = value.toLowerCase();
	return ELEMENT_KEYS.find( ( key ) => needle.includes( key ) );
}
