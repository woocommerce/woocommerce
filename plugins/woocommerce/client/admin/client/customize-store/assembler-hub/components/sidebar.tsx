/**
 * External dependencies
 */
import { createContext, useCallback, useState } from '@wordpress/element';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './style.scss';

export enum SidebarNavigationAnimationDirection {
	Forward = 'forward',
	Back = 'back',
}

type SidebarNavigationContextType = {
	navigate: ( direction: SidebarNavigationAnimationDirection ) => void;
};

export const SidebarNavigationContext =
	createContext< SidebarNavigationContextType >( {
		navigate: () => void 0,
	} );

export function SidebarContent( { children }: { children: JSX.Element } ) {
	const [ navState, setNavState ] = useState< {
		direction: SidebarNavigationAnimationDirection | null;
	} >( {
		direction: null,
	} );

	const navigate = useCallback(
		( direction: SidebarNavigationAnimationDirection ) => {
			setNavState( { direction } );
		},
		[]
	);

	const wrapperCls = clsx(
		'woocommerce-customize-store-edit-site-sidebar__screen-wrapper',
		{
			'slide-from-left': navState.direction === 'back',
			'slide-from-right': navState.direction === 'forward',
		}
	);

	return (
		<SidebarNavigationContext.Provider value={ { navigate } }>
			<div className={ wrapperCls }>{ children }</div>
		</SidebarNavigationContext.Provider>
	);
}
