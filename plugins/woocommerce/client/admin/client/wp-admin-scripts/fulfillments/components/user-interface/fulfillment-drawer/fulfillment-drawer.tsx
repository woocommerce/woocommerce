/**
 * External dependencies
 */
import React, { useEffect, useRef } from 'react';

/**
 * Internal dependencies
 */
import NewFulfillmentForm from '../../fulfillments/new-fulfillment-form';
import { ErrorBoundary } from '~/error-boundary';
import FulfillmentsList from '../../fulfillments/fulfillments-list';
import FulfillmentsDrawerHeader from './fulfillment-drawer-header';
import { FulfillmentDrawerProvider } from '../../../context/drawer-context';
import './fulfillment-drawer.scss';
import FulfillmentDrawerBody from './fulfillment-drawer-body';

interface Props {
	isOpen: boolean;
	hasBackdrop?: boolean;
	onClose: () => void;
	orderId: number | null;
}

const FulfillmentDrawer: React.FC< Props > = ( {
	isOpen,
	hasBackdrop = false,
	onClose,
	orderId,
} ) => {
	const drawerRef = useRef< HTMLDivElement >( null );
	const previousFocusRef = useRef< HTMLElement | null >( null );

	// Focus management when drawer opens/closes
	useEffect( () => {
		let rafId1: number;
		let rafId2: number;
		if ( isOpen ) {
			const drawerElement = drawerRef.current;
			if ( drawerElement ) {
				// Save the previous focused element to restore focus later
				previousFocusRef.current = drawerElement.ownerDocument
					.activeElement as HTMLElement;

				// Focus the drawer container itself after it's fully rendered
				// This allows natural scrolling and keyboard navigation within
				rafId1 = requestAnimationFrame( () => {
					rafId2 = requestAnimationFrame( () => {
						if ( drawerElement ) {
							drawerElement.focus();
						}
					} );
				} );
			}
		} else if ( previousFocusRef.current?.isConnected ) {
			// Restore focus to the previously focused element
			previousFocusRef.current.focus();
		}
		return () => {
			cancelAnimationFrame( rafId1 );
			cancelAnimationFrame( rafId2 );
		};
	}, [ isOpen ] );

	// Handle keyboard navigation: Escape to close and focus trapping
	useEffect( () => {
		const handleKeyDown = ( event: KeyboardEvent ) => {
			if ( ! isOpen ) return;

			// Close drawer on Escape key
			if ( event.key === 'Escape' ) {
				onClose();
				return;
			}

			// Focus trap: Only trap Tab navigation, allow all other keys (including scrolling)
			if ( event.key === 'Tab' ) {
				const drawerElement = drawerRef.current;
				if ( ! drawerElement ) return;

				const focusableElements = drawerElement.querySelectorAll(
					'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"]):not([disabled])'
				);

				if ( focusableElements.length === 0 ) return;

				const firstElement = focusableElements[ 0 ] as HTMLElement;
				const lastElement = focusableElements[
					focusableElements.length - 1
				] as HTMLElement;
				const activeElement = drawerElement.ownerDocument
					.activeElement as HTMLElement;

				// Shift+Tab: If focus is on first element or the drawer panel itself, move to last
				if ( event.shiftKey ) {
					if (
						activeElement === firstElement ||
						activeElement === drawerElement
					) {
						event.preventDefault();
						lastElement?.focus();
					}
				} else if ( activeElement === lastElement ) {
					// Tab: If focus is on last element, move to first
					event.preventDefault();
					firstElement?.focus();
				}
			}
		};

		if ( isOpen ) {
			document.addEventListener( 'keydown', handleKeyDown );
		}

		return () => {
			document.removeEventListener( 'keydown', handleKeyDown );
		};
	}, [ isOpen, onClose ] );

	return (
		<>
			{ hasBackdrop && (
				<div
					className="woocommerce-fulfillment-drawer__backdrop"
					onClick={ onClose }
					role="presentation"
					style={ { display: isOpen ? 'block' : 'none' } }
					aria-hidden={ ! isOpen }
				/>
			) }
			<div className="woocommerce-fulfillment-drawer">
				<div
					ref={ drawerRef }
					className={ [
						'woocommerce-fulfillment-drawer__panel',
						isOpen ? 'is-open' : 'is-closed',
					].join( ' ' ) }
					role="dialog"
					aria-modal="true"
					aria-labelledby="fulfillment-drawer-header"
					aria-hidden={ ! isOpen }
					tabIndex={ -1 }
				>
					<ErrorBoundary>
						<FulfillmentDrawerProvider orderId={ orderId }>
							<FulfillmentsDrawerHeader onClose={ onClose } />
							<FulfillmentDrawerBody>
								<NewFulfillmentForm />
								<FulfillmentsList />
							</FulfillmentDrawerBody>
						</FulfillmentDrawerProvider>
					</ErrorBoundary>
				</div>
			</div>
		</>
	);
};

export default FulfillmentDrawer;
