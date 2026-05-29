/**
 * External dependencies
 */
import { useContext } from 'react';

/**
 * Internal dependencies
 */
import { PrototypeFlagsContext } from './PrototypeFlagsContext';

export function usePrototypeFlags() {
	return useContext( PrototypeFlagsContext );
}
