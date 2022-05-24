/**
 * External dependencies
 */
import {
	createElement,
	useState,
	useCallback,
	useEffect,
	useRef,
} from '@wordpress/element';
import { CSSTransitionProps } from 'react-transition-group/CSSTransition';
import { CSSTransition } from 'react-transition-group';

export type VerticalCSSTransitionProps<
	Ref extends HTMLElement | undefined = undefined
> = CSSTransitionProps< Ref > & {
	defaultStyle?: React.CSSProperties;
};

function getContainerHeight( container: HTMLDivElement ) {
	let containerHeight = 0;
	for ( const child of container.children ) {
		containerHeight += child.clientHeight;
		const style = window.getComputedStyle( child );

		containerHeight += parseInt( style.marginTop, 10 ) || 0;
		containerHeight += parseInt( style.marginBottom, 10 ) || 0;
	}
	return containerHeight;
}

/**
 * VerticalCSSTransition is a wrapper for CSSTransition, automatically adding a vertical height transition.
 * The maxHeight is calculated through JS, something CSS does not support.
 */
export const VerticalCSSTransition: React.FC< VerticalCSSTransitionProps > = ( {
	children,
	defaultStyle,
	...props
} ) => {
	const [ containerHeight, setContainerHeight ] = useState( 0 );
	const [ transitionIn, setTransitionIn ] = useState( props.in || false );
	const cssTransitionRef = useRef< CSSTransition< HTMLElement > | null >(
		null
	);
	const collapseContainerRef = useCallback(
		( containerElement: HTMLDivElement ) => {
			if ( containerElement ) {
				setContainerHeight( getContainerHeight( containerElement ) );
			}
		},
		[ children ]
	);

	useEffect( () => {
		setTransitionIn( props.in || false );
	}, [ props.in ] );

	const getTimeouts = () => {
		const { timeout } = props;
		let exit, enter, appear;

		if ( typeof timeout === 'number' ) {
			exit = enter = appear = timeout;
		}

		if ( timeout !== undefined && typeof timeout !== 'number' ) {
			exit = timeout.exit;
			enter = timeout.enter;
			appear = timeout.appear !== undefined ? timeout.appear : enter;
		}
		return { exit, enter, appear };
	};

	const transitionStyles = {
		entered: { maxHeight: containerHeight },
		entering: { maxHeight: containerHeight },
		exiting: { maxHeight: 0 },
		exited: { maxHeight: 0 },
	};

	const getTransitionStyle = (
		state: 'entering' | 'entered' | 'exiting' | 'exited'
	) => {
		const timeouts = getTimeouts();
		const appearing =
			cssTransitionRef.current &&
			cssTransitionRef.current.context &&
			cssTransitionRef.current.context.isMounting;
		let duration;
		if ( state.startsWith( 'enter' ) ) {
			duration = timeouts[ appearing ? 'enter' : 'appear' ];
		} else {
			duration = timeouts.exit;
		}

		const styles: React.CSSProperties = {
			transitionProperty: 'max-height',
			transitionDuration:
				duration === undefined ? '500ms' : duration + 'ms',
			overflow: 'hidden',
			...( defaultStyle || {} ),
			...transitionStyles[ state ],
		};
		// only include transition styles when entering or exiting.
		if ( state !== 'entering' && state !== 'exiting' ) {
			delete styles.transitionDuration;
			delete styles.transition;
			delete styles.transitionProperty;
		}
		// Remove maxHeight when entered, so we do not need to worry about nested items changing height while expanded.
		if ( state === 'entered' && props.in ) {
			delete styles.maxHeight;
		}
		return styles;
	};

	return (
		<CSSTransition
			{ ...props }
			in={ transitionIn }
			ref={ cssTransitionRef }
		>
			{ ( state: 'entering' | 'entered' | 'exiting' | 'exited' ) => (
				<div
					className="vertical-css-transition-container"
					style={ getTransitionStyle( state ) }
					ref={ collapseContainerRef }
				>
					{ children }
				</div>
			) }
		</CSSTransition>
	);
};
