/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect, select as WPSelect } from '@wordpress/data';
import { createElement, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { Options } from './types';

export const useOptionsHydration = ( data: Options ) => {
	const dataRef = useRef( data );

	// @ts-expect-error registry is not defined in the wp.data typings
	useSelect( ( select: typeof WPSelect, registry ) => {
		if ( ! dataRef.current ) {
			return;
		}

		const { isResolving, hasFinishedResolution } = select( STORE_NAME );
		const { startResolution, finishResolution, receiveOptions } =
			registry.dispatch( STORE_NAME );
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
};

export const withOptionsHydration = < ComponentProps, >( data: Options ) =>
	createHigherOrderComponent< Record< string, unknown >, ComponentProps >(
		( OriginalComponent ) => ( props ) => {
			useOptionsHydration( data );

			return <OriginalComponent { ...props } />;
		},
		'withOptionsHydration'
	);
