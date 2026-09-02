/**
 * External dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

type ExpressPaymentFocus = {
	expressPaymentWrapperRef: React.MutableRefObject< HTMLElement | null >;
	focusedExpressPaymentMethod: string | null;
};

export const useExpressPaymentFocus = (
	enabled: boolean
): ExpressPaymentFocus => {
	const expressPaymentWrapperRef = useRef< HTMLElement | null >( null );
	const focusSyncIntervalRef = useRef< ReturnType<
		typeof setInterval
	> | null >( null );
	const [ focusedExpressPaymentMethod, setFocusedExpressPaymentMethod ] =
		useState< string | null >( null );

	const stopFocusSyncInterval = useCallback( () => {
		if ( focusSyncIntervalRef.current ) {
			clearInterval( focusSyncIntervalRef.current );
			focusSyncIntervalRef.current = null;
		}
	}, [] );

	const getFocusedExpressPaymentMethod = useCallback( () => {
		const wrapper = expressPaymentWrapperRef.current;
		const activeElement = wrapper?.ownerDocument.activeElement;

		if ( ! wrapper || ! ( activeElement instanceof Element ) ) {
			return {
				focusedPaymentMethod: null,
				isExpressPaymentIframe: false,
			};
		}

		const item = activeElement.closest( '[id^="express-payment-method-"]' );
		const isExpressPaymentIframe =
			activeElement instanceof HTMLIFrameElement &&
			wrapper.contains( activeElement );

		if ( item && wrapper.contains( item ) ) {
			return {
				focusedPaymentMethod: item.id.replace(
					'express-payment-method-',
					''
				),
				isExpressPaymentIframe,
			};
		}

		return {
			focusedPaymentMethod: null,
			isExpressPaymentIframe: false,
		};
	}, [] );

	const syncFocusedExpressPaymentMethod = useCallback( () => {
		const { focusedPaymentMethod, isExpressPaymentIframe } =
			getFocusedExpressPaymentMethod();

		setFocusedExpressPaymentMethod( focusedPaymentMethod );

		if ( ! isExpressPaymentIframe ) {
			stopFocusSyncInterval();
			return;
		}

		if ( ! focusSyncIntervalRef.current ) {
			focusSyncIntervalRef.current = setInterval( () => {
				const {
					focusedPaymentMethod: nextFocusedPaymentMethod,
					isExpressPaymentIframe: nextIsExpressPaymentIframe,
				} = getFocusedExpressPaymentMethod();

				setFocusedExpressPaymentMethod( nextFocusedPaymentMethod );

				if ( ! nextIsExpressPaymentIframe ) {
					stopFocusSyncInterval();
				}
			}, 100 );
		}
	}, [ getFocusedExpressPaymentMethod, stopFocusSyncInterval ] );

	useEffect( () => {
		if ( ! enabled ) {
			setFocusedExpressPaymentMethod( null );
			stopFocusSyncInterval();
			return;
		}

		const wrapper = expressPaymentWrapperRef.current;

		if ( ! wrapper ) {
			return;
		}

		const doc = wrapper.ownerDocument;
		const win = doc.defaultView;
		const syncSoon = () => {
			setTimeout( syncFocusedExpressPaymentMethod, 0 );
		};

		doc.addEventListener( 'focusin', syncFocusedExpressPaymentMethod );
		doc.addEventListener( 'focusout', syncSoon );
		win?.addEventListener( 'blur', syncFocusedExpressPaymentMethod );
		win?.addEventListener( 'focus', syncSoon );

		return () => {
			stopFocusSyncInterval();
			doc.removeEventListener(
				'focusin',
				syncFocusedExpressPaymentMethod
			);
			doc.removeEventListener( 'focusout', syncSoon );
			win?.removeEventListener( 'blur', syncFocusedExpressPaymentMethod );
			win?.removeEventListener( 'focus', syncSoon );
		};
	}, [ enabled, stopFocusSyncInterval, syncFocusedExpressPaymentMethod ] );

	return {
		expressPaymentWrapperRef,
		focusedExpressPaymentMethod,
	};
};
