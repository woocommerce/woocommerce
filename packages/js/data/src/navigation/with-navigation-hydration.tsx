/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import { createElement, useEffect } from '@wordpress/element';

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
export const withNavigationHydration = ( data: { menuItems: MenuItem[] } ) =>
	createHigherOrderComponent< Record< string, unknown > >(
		( OriginalComponent ) => ( props ) => {
			const shouldHydrate = useSelect( ( select ) => {
				if ( ! data ) {
					return;
				}

				const { isResolving, hasFinishedResolution } =
					select( STORE_NAME );
				return (
					! isResolving( 'getMenuItems' ) &&
					! hasFinishedResolution( 'getMenuItems' )
				);
			} );
			const { startResolution, finishResolution, setMenuItems } =
				useDispatch( STORE_NAME );

			useEffect( () => {
				if ( ! shouldHydrate ) {
					return;
				}
				startResolution( 'getMenuItems', [] );
				setMenuItems( data.menuItems );
				finishResolution( 'getMenuItems', [] );
			}, [ shouldHydrate ] );

			return <OriginalComponent { ...props } />;
		},
		'withNavigationHydration'
	);
