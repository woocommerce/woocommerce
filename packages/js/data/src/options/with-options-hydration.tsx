/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect, useDispatch, select as WPSelect } from '@wordpress/data';
import { createElement, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { Options } from './types';

export const useOptionsHydration = ( data: Options ) => {
	const shouldHydrate = useSelect( ( select: typeof WPSelect ) => {
		const { isResolving, hasFinishedResolution } = select( STORE_NAME );

		if ( ! data ) {
			return {};
		}

		return Object.fromEntries(
			Object.keys( data ).map( ( name ) => {
				const hydrate =
					! isResolving( 'getOption', [ name ] ) &&
					! hasFinishedResolution( 'getOption', [ name ] );
				return [ name, hydrate ];
			} )
		);
	}, [] );

	const { startResolution, finishResolution, receiveOptions } =
		useDispatch( STORE_NAME );

	useEffect( () => {
		Object.entries( shouldHydrate ).forEach( ( [ name, hydrate ] ) => {
			if ( hydrate ) {
				startResolution( 'getOption', [ name ] );
				receiveOptions( { [ name ]: data[ name ] } );
				finishResolution( 'getOption', [ name ] );
			}
		} );
	}, [ shouldHydrate ] );
};

export const withOptionsHydration = ( data: Options ) =>
	createHigherOrderComponent< Record< string, unknown > >(
		( OriginalComponent ) => ( props ) => {
			useOptionsHydration( data );

			return <OriginalComponent { ...props } />;
		},
		'withOptionsHydration'
	);
