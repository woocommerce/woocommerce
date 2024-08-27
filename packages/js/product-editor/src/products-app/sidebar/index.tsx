/**
 * External dependencies
 */
import {
	createElement,
	createContext,
	useContext,
	useState,
	useRef,
	useLayoutEffect,
} from '@wordpress/element';
import { focus } from '@wordpress/dom';
import classNames from 'classnames';

export const SidebarNavigationContext = createContext< {
	get(): { direction: string | null; focusSelector?: string };
	navigate( direction: string, focusSelector?: string ): void;
} >( {
	get: () => ( { direction: null } ),
	navigate: () => {},
} );
// Focus a sidebar element after a navigation. The element to focus is either
// specified by `focusSelector` (when navigating back) or it is the first
// tabbable element (usually the "Back" button).
function focusSidebarElement(
	el: HTMLElement,
	direction: string | null,
	focusSelector?: string
) {
	let elementToFocus: HTMLElement | null = null;
	if ( direction === 'back' && focusSelector ) {
		elementToFocus = el.querySelector( focusSelector );
	}
	if ( direction !== null && ! elementToFocus ) {
		const [ firstTabbable ] = focus.tabbable.find( el );
		elementToFocus = firstTabbable ?? el;
	}
	elementToFocus?.focus();
}

// Navigation state that is updated when navigating back or forward. Helps us
// manage the animations and also focus.
function createNavState() {
	let state: { direction: string | null; focusSelector?: string } = {
		direction: null,
	};

	return {
		get() {
			return state;
		},
		navigate( direction: string, focusSelector?: string ) {
			state = {
				direction,
				focusSelector:
					direction === 'forward' && focusSelector
						? focusSelector
						: state.focusSelector,
			};
		},
	};
}

function SidebarContentWrapper( { children }: { children: React.ReactNode } ) {
	const navState = useContext( SidebarNavigationContext );
	const wrapperRef = useRef< HTMLDivElement | null >( null );
	const [ navAnimation, setNavAnimation ] = useState< string | null >( null );

	useLayoutEffect( () => {
		const { direction, focusSelector } = navState.get();
		if ( wrapperRef.current && direction ) {
			focusSidebarElement( wrapperRef.current, direction, focusSelector );
		}
		setNavAnimation( direction );
	}, [ navState ] );

	const wrapperCls = classNames( 'edit-site-sidebar__screen-wrapper', {
		'slide-from-left': navAnimation === 'back',
		'slide-from-right': navAnimation === 'forward',
	} );

	return (
		<div ref={ wrapperRef } className={ wrapperCls }>
			{ children }
		</div>
	);
}

export default function SidebarContent( {
	routeKey,
	children,
}: {
	routeKey: string;
	children: React.ReactNode;
} ) {
	const [ navState ] = useState( createNavState );

	return (
		<SidebarNavigationContext.Provider value={ navState }>
			<div className="edit-site-sidebar__content">
				<SidebarContentWrapper key={ routeKey }>
					{ children }
				</SidebarContentWrapper>
			</div>
		</SidebarNavigationContext.Provider>
	);
}
