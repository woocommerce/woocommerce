/**
 * External dependencies
 */
import { useRegistry } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import {
	store as interfaceStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/interface';

export const RegisterStores = () => {
	const registry = useRegistry();

	useEffect( () => {
		// @ts-expect-error No types for this exist yet.
		registry.register( interfaceStore );
	}, [ registry ] );

	return null;
};
