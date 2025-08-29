/**
 * External dependencies
 */
import { Suspense, useRef, useCallback, useEffect } from '@wordpress/element';
import clsx from 'clsx';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useFocusOnMount from '~/hooks/useFocusOnMount';
import useFocusOutside from '~/hooks/useFocusOutside';

export const Panel = ( {
	content,
	isPanelOpen,
	isPanelSwitching,
	currentTab,
	tab,
	closePanel,
	clearPanel,
} ) => {
	const panelClass = 'woocommerce-layout__activity-panel-wrapper';

	const focusOnMountRef = useFocusOnMount();
	const containerRef = useRef( null );
	const iframeInteractionRef = useRef( false );

	// Listen for stripe-refresh events and close panel.
	useEffect( () => {
		const handleStripeRefresh = () => {
			if ( isPanelOpen ) {
				closePanel();
			}
		};
		
		window.addEventListener( 'stripe-refresh', handleStripeRefresh );
		return () => window.removeEventListener( 'stripe-refresh', handleStripeRefresh );
	}, [ isPanelOpen, closePanel ] );

	// Add click-based closing when Stripe iframes are present
	useEffect( () => {
		if ( ! isPanelOpen ) {
			return;
		}

		const handleDocumentClick = ( e ) => {
			// Check if there are visible Stripe banners with iframes
			const stripeBanners = document.querySelectorAll(
				'.woocommerce-embedded-connect-notification-banner, .stripe-notifications-banner-wrapper'
			);
			
			let hasVisibleIframes = false;
			stripeBanners.forEach( ( banner ) => {
				if ( banner.offsetParent !== null ) {
					const iframes = banner.querySelectorAll( 'iframe' );
					if ( iframes.length > 0 ) {
						hasVisibleIframes = true;
					}
				}
			} );

			// Only use click-based closing when iframes are present
			if ( hasVisibleIframes ) {
				// Check if click is outside the activity panel and outside Stripe banners
				const panelEl = containerRef.current;
				const isClickOutsidePanel = panelEl && ! panelEl.contains( e.target );
				
				const isClickInsideStripeBanner = e.target.closest( 
					'.woocommerce-embedded-connect-notification-banner, .stripe-notifications-banner-wrapper'
				);

				if ( isClickOutsidePanel && ! isClickInsideStripeBanner ) {
					closePanel();
				}
			}
		};

		document.addEventListener( 'click', handleDocumentClick, true );
		return () => {
			document.removeEventListener( 'click', handleDocumentClick, true );
		};
	}, [ isPanelOpen, closePanel ] );

	const handleFocusOutside = ( event ) => {
		// Check if there are any visible Stripe banners with iframes
		const stripeBanners = document.querySelectorAll(
			'.woocommerce-embedded-connect-notification-banner, .stripe-notifications-banner-wrapper'
		);
		
		let hasVisibleIframes = false;
		stripeBanners.forEach( ( banner ) => {
			// Only check if the banner itself is visible
			if ( banner.offsetParent !== null ) {
				const iframes = banner.querySelectorAll( 'iframe' );
				if ( iframes.length > 0 ) {
					hasVisibleIframes = true;
				}
			}
		} );

		// If there are visible Stripe iframes, don't close on any focus events
		// This is a simple but effective approach for iframe interactions
		if ( hasVisibleIframes ) {
			return;
		}

		// Check both event.relatedTarget and event.target for better coverage.
		const targetElement = event.relatedTarget || event.target;
		
		// Check if focus is moving to elements inside Stripe banners
		const isInStripeBanner =
			targetElement &&
			targetElement.closest &&
			targetElement.closest(
				'.woocommerce-embedded-connect-notification-banner, .stripe-notifications-banner-wrapper'
			);
		if ( isInStripeBanner ) {
			return;
		}
		
		// More precise detection of elements that should prevent panel closing
		const isClickOnModalOrSnackbar =
			targetElement &&
			targetElement.closest &&
			( targetElement.closest( '.woocommerce-inbox-dismiss-confirmation_modal' ) ||
			  targetElement.closest( '.components-snackbar__action' ) ||
			  targetElement.closest( '.components-modal__screen-overlay' ) ||
			  targetElement.closest( '.components-popover' ) );

		if ( isPanelOpen && ! isClickOnModalOrSnackbar ) {
			closePanel();
		}
	};

	const possibleFocusPanel = () => {
		if ( ! containerRef.current || ! isPanelOpen || ! tab ) {
			return;
		}

		focusOnMountRef( containerRef.current );
	};

	const finishTransition = ( e ) => {
		if ( e && e.propertyName === 'transform' ) {
			clearPanel();
			possibleFocusPanel();
		}
	};

	const useFocusOutsideProps = useFocusOutside( handleFocusOutside );

	const mergedContainerRef = useCallback( ( node ) => {
		containerRef.current = node;
		focusOnMountRef( node );
	}, [] );

	if ( ! tab ) {
		return <div className={ panelClass } />;
	}

	if ( ! content ) {
		return null;
	}

	const classNames = clsx( panelClass, {
		'is-open': isPanelOpen,
		'is-switching': isPanelSwitching,
	} );

	return (
		<div
			className={ classNames }
			tabIndex={ 0 }
			role="tabpanel"
			aria-label={ tab.title }
			onTransitionEnd={ finishTransition }
			{ ...useFocusOutsideProps }
			ref={ mergedContainerRef }
		>
			<div
				className="woocommerce-layout__activity-panel-content"
				key={ 'activity-panel-' + currentTab }
				id={ 'activity-panel-' + currentTab }
			>
				<Suspense fallback={ <Spinner /> }>{ content }</Suspense>
			</div>
		</div>
	);
};

export default Panel;
