/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

export const withOptionsHydration = ( data ) => ( OriginalComponent ) => {
	return ( props ) => {
		const dataRef = useRef( data );

		useSelect( ( select, registry ) => {
			if ( ! dataRef.current ) {
				return;
			}

			const { isResolving, hasFinishedResolution } = select( STORE_NAME );
			const {
				startResolution,
				finishResolution,
				receiveOptions,
			} = registry.dispatch( STORE_NAME );
			const names = Object.keys( dataRef.current );

			names.forEach( ( name ) => {
				if (
					! isResolving( 'getOption', [ name ] ) &&
					! hasFinishedResolution( 'getOption', [ name ] )
				) {
					startResolution( 'getOption', [ name ] );
					receiveOptions( { [ name ]: dataRef.current[ name ] } );
					finishResolution( 'getOption', [ name ] );
				}
			} );
		}, [] );

		return <OriginalComponent { ...props } />;
	};
};
