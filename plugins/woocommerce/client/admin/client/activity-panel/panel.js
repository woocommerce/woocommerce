/**
 * External dependencies
 */
import { Suspense, useRef, useCallback } from '@wordpress/element';
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

	const handleFocusOutside = ( event ) => {
		console.log('handleFocusOutside called:', {
			event: event.type,
			target: event.target,
			relatedTarget: event.relatedTarget,
			isPanelOpen
		});

		// Check if there are any Stripe notification banner components rendered.
		const hasStripeComponents = document.querySelector( '.stripe-notifications-banner-wrapper' ) ||
			document.querySelector( '.woocommerce-embedded-connect-notification-banner' );
		
		if ( hasStripeComponents ) {
			// If Stripe components are present, don't close on blur events
			// as they use iframes which cause blur but shouldn't close the panel.
			return;
		}

		// Check both event.relatedTarget and event.target for better coverage.
		const targetElement = event.relatedTarget || event.target;
		const isClickOnModalOrSnackbar =
			targetElement &&
			( targetElement.closest &&
				( targetElement.closest(
					'.woocommerce-inbox-dismiss-confirmation_modal'
				) ||
					targetElement.closest( '.components-snackbar__action' ) ) );

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
