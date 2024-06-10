/**
 * External dependencies
 */
import { focus } from '@wordpress/dom';
import {
	createContext,
	useCallback,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
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

export function SidebarContent( {
	children,
}: {
	routeKey: string;
	children: JSX.Element;
} ) {
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

	const wrapperRef = useRef< HTMLElement >();
	useEffect( () => {
		let elementToFocus;
		if ( navState.direction !== null && ! elementToFocus ) {
			const [ firstTabbable ] = wrapperRef.current
				? focus.tabbable.find( wrapperRef?.current )
				: [];
			elementToFocus = firstTabbable ?? wrapperRef.current;
		}
		( elementToFocus as HTMLElement | undefined )?.focus();
	}, [ navState ] );

	const wrapperCls = clsx( 'edit-site-sidebar__screen-wrapper', {
		'slide-from-left': navState.direction === 'back',
		'slide-from-right': navState.direction === 'forward',
	} );

	return (
		<SidebarNavigationContext.Provider value={ { navigate } }>
			<div className={ wrapperCls }>{ children }</div>
		</SidebarNavigationContext.Provider>
	);
}
