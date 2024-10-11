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
		const isClickOnModalOrSnackbar =
			event.relatedTarget &&
			( event.relatedTarget.closest(
				'.woocommerce-inbox-dismiss-confirmation_modal'
			) ||
				event.relatedTarget.closest( '.components-snackbar__action' ) );

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
