/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

interface ScrollToTopProps {
	focusableSelector?: string;
}

const maybeScrollToTop = ( scrollPoint: HTMLElement ): void => {
	const yPos = scrollPoint.getBoundingClientRect().bottom;
	const isScrollPointVisible = yPos >= 0 && yPos <= window.innerHeight;

	if ( ! isScrollPointVisible ) {
		scrollPoint.scrollIntoView();
	}
};

const moveFocusToTop = (
	scrollPoint: HTMLElement,
	focusableSelector: string
): void => {
	const focusableElements =
		scrollPoint.parentElement?.querySelectorAll( focusableSelector ) || [];

	if ( focusableElements.length ) {
		( focusableElements[ 0 ] as HTMLElement )?.focus();
	}
};

const scrollToHTMLElement = (
	scrollPoint: HTMLElement,
	{ focusableSelector }: ScrollToTopProps
): void => {
	if ( ! window || ! Number.isFinite( window.innerHeight ) ) {
		return;
	}

	maybeScrollToTop( scrollPoint );

	if ( focusableSelector ) {
		moveFocusToTop( scrollPoint, focusableSelector );
	}
};

/**
 * HOC that provides a function to scroll to the top of the component.
 */
const withScrollToTop = (
	OriginalComponent: React.FunctionComponent< Record< string, unknown > >
) => {
	return ( props: Record< string, unknown > ): JSX.Element => {
		const scrollPointRef = useRef< HTMLDivElement >( null );
		const scrollToTop = ( args: ScrollToTopProps ) => {
			if ( scrollPointRef.current !== null ) {
				scrollToHTMLElement( scrollPointRef.current, args );
			}
		};
		return (
			<>
				<div
					className="with-scroll-to-top__scroll-point"
					ref={ scrollPointRef }
					aria-hidden
				/>
				<OriginalComponent { ...props } scrollToTop={ scrollToTop } />
			</>
		);
	};
};

export default withScrollToTop;
