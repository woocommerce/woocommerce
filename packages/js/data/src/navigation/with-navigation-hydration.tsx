/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { createElement, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { MenuItem } from './types';

/**
 * Higher-order component used to hydrate navigation data.
 *
 * @param {Object}     data           Data object with menu items and site information.
 * @param {MenuItem[]} data.menuItems Menu items to hydrate.
 */
export const withNavigationHydration = < ComponentProps, >( data: {
	menuItems: MenuItem[];
} ) =>
	createHigherOrderComponent< Record< string, unknown >, ComponentProps >(
		( OriginalComponent ) => ( props ) => {
			const dataRef = useRef( data );

			// @ts-expect-error // @ts-expect-error registry is not defined in the wp.data typings
			useSelect( ( select, registry ) => {
				if ( ! dataRef.current ) {
					return;
				}

				const { isResolving, hasFinishedResolution } =
					select( STORE_NAME );
				const { startResolution, finishResolution, setMenuItems } =
					registry.dispatch( STORE_NAME );

				if (
					! isResolving( 'getMenuItems' ) &&
					! hasFinishedResolution( 'getMenuItems' )
				) {
					startResolution( 'getMenuItems', [] );
					setMenuItems( dataRef.current.menuItems );
					finishResolution( 'getMenuItems', [] );
				}
			} );

			return <OriginalComponent { ...props } />;
		},
		'withNavigationHydration'
	);
