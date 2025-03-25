/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import { createElement, useEffect } from '@wordpress/element';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { store } from './';
import { Options } from './types';

export const useOptionsHydration = ( data: Options ) => {
	const shouldHydrate = useSelect( ( select ) => {
		const { isResolving, hasFinishedResolution } = select( store );

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
		useDispatch( store );

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
	createHigherOrderComponent<
		ComponentType< Record< string, unknown > >,
		ComponentType< Record< string, unknown > >
	>(
		( OriginalComponent ) => ( props ) => {
			useOptionsHydration( data );

			return <OriginalComponent { ...props } />;
		},
		'withOptionsHydration'
	);
