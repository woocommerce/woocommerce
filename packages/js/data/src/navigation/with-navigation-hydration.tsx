/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import { createElement, useEffect } from '@wordpress/element';
import { SelectFromMap } from '@automattic/data-stores';
import type { ComponentType } from 'react';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';
import { MenuItem } from './types';
import { WPDataSelectors } from '../types';
import * as selectors from './selectors';

/**
 * Higher-order component used to hydrate navigation data.
 *
 * @param {Object}     data           Data object with menu items and site information.
 * @param {MenuItem[]} data.menuItems Menu items to hydrate.
 */
export const withNavigationHydration = ( data: { menuItems: MenuItem[] } ) =>
	createHigherOrderComponent<
		ComponentType< Record< string, unknown > >,
		ComponentType< Record< string, unknown > >
	>(
		( OriginalComponent ) => ( props ) => {
			deprecated( 'withNavigationHydration', {} );
			const shouldHydrate = useSelect(
				(
					select: (
						key: typeof STORE_NAME
					) => SelectFromMap< typeof selectors > & WPDataSelectors
				) => {
					if ( ! data ) {
						return;
					}

					const { isResolving, hasFinishedResolution } =
						select( STORE_NAME );
					return (
						! isResolving( 'getMenuItems' ) &&
						! hasFinishedResolution( 'getMenuItems' )
					);
				},
				[]
			);

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
