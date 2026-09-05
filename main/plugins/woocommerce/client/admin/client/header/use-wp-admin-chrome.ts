/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

type ActiveMetaIcon = 'screen-options' | 'help' | null;

type WpAdminChrome = {
	hasH1: boolean;
	hasScreenOptions: boolean;
	hasContextualHelp: boolean;
	activeMetaIcon: ActiveMetaIcon;
	triggerMetaIcon: (
		which: Exclude< ActiveMetaIcon, null >,
		triggerId: string
	) => void;
};

/**
 * Detect and orchestrate wp-admin chrome that's already rendered on classic
 * admin pages (Edit Order, Edit Product, Settings, etc.). On a non-embedded
 * Woo-custom page the queried elements aren't in the DOM and every return
 * value falls back to a no-op default.
 *
 * Three signals are read synchronously on mount and re-read on route changes:
 *   - `.wrap > h1.wp-heading-inline` — wp-admin's own page title. When present
 *     the embed header should suppress its own <h1> to avoid duplicating it.
 *   - `#screen-options-link-wrap` and `#contextual-help-link-wrap` — the
 *     wp-admin meta-toggle wraps. When present we render proxy gear / ? icons
 *     in the floating header and hide the originals via CSS.
 *
 * The hook also owns the meta-icon orchestration:
 *   - `activeMetaIcon` stays in sync with each trigger's `aria-expanded` via a
 *     `MutationObserver`, so React state updates only happen *after* a click
 *     has fully settled — never during.
 *   - `triggerMetaIcon` closes any open activity-panel tab and the other
 *     wp-admin dropdown before opening the target one (mutual exclusion).
 *   - A document-level capture-phase click listener closes any open wp-admin
 *     dropdown when an activity-panel tab is clicked. (Sync goes both ways.)
 *
 * Lazy initial state reads the DOM on first render so embed pages don't flash
 * a duplicate-title frame before the first effect commits.
 */
export const useWpAdminChrome = (
	query: Record< string, string >
): WpAdminChrome => {
	const detectWpAdminChrome = () => ( {
		hasH1: !! document.querySelector( '.wrap > h1.wp-heading-inline' ),
		hasScreenOptions: !! document.querySelector(
			'#screen-options-link-wrap'
		),
		hasContextualHelp: !! document.querySelector(
			'#contextual-help-link-wrap'
		),
	} );
	const [ chrome, setChrome ] = useState( detectWpAdminChrome );
	useEffect( () => {
		setChrome( detectWpAdminChrome() );
	}, [ query ] );
	const { hasH1, hasScreenOptions, hasContextualHelp } = chrome;

	const [ activeMetaIcon, setActiveMetaIcon ] =
		useState< ActiveMetaIcon >( null );

	// Reverse-direction sync: when an activity-panel tab is clicked AND a
	// wp-admin dropdown is currently open, close the dropdown. We don't update
	// React state from this handler (state syncs reactively via the
	// MutationObserver below), so no setTimeout deferral is needed.
	useEffect( () => {
		const handler = ( e: Event ) => {
			const target = e.target as HTMLElement | null;
			if (
				! target?.closest( '.woocommerce-layout__activity-panel-tab' )
			) {
				return;
			}
			document
				.querySelector< HTMLButtonElement >(
					'#show-settings-link[aria-expanded="true"]'
				)
				?.click();
			document
				.querySelector< HTMLButtonElement >(
					'#contextual-help-link[aria-expanded="true"]'
				)
				?.click();
		};
		document.addEventListener( 'click', handler, true );
		return () => document.removeEventListener( 'click', handler, true );
	}, [] );

	// Keep activeMetaIcon in sync with the actual wp-admin dropdown state by
	// observing aria-expanded changes on the trigger buttons.
	useEffect( () => {
		if ( ! hasScreenOptions && ! hasContextualHelp ) {
			setActiveMetaIcon( null );
			return;
		}
		const screenOptBtn = document.querySelector< HTMLButtonElement >(
			'#show-settings-link'
		);
		const helpBtn = document.querySelector< HTMLButtonElement >(
			'#contextual-help-link'
		);
		const sync = () => {
			const screenOpen =
				screenOptBtn?.getAttribute( 'aria-expanded' ) === 'true';
			const helpOpen =
				helpBtn?.getAttribute( 'aria-expanded' ) === 'true';
			let next: ActiveMetaIcon = null;
			if ( screenOpen ) {
				next = 'screen-options';
			} else if ( helpOpen ) {
				next = 'help';
			}
			setActiveMetaIcon( next );
		};
		sync();
		const observer = new MutationObserver( sync );
		const opts = {
			attributes: true,
			attributeFilter: [ 'aria-expanded' ],
		};
		if ( screenOptBtn ) observer.observe( screenOptBtn, opts );
		if ( helpBtn ) observer.observe( helpBtn, opts );
		return () => observer.disconnect();
	}, [ hasScreenOptions, hasContextualHelp ] );

	const triggerMetaIcon = (
		which: Exclude< ActiveMetaIcon, null >,
		triggerId: string
	) => {
		// Close any open activity-panel tab so the five icons act as one group.
		document
			.querySelector< HTMLButtonElement >(
				'.woocommerce-layout__activity-panel-tab.is-active'
			)
			?.click();
		// Close the OTHER wp-admin dropdown if open (mutual exclusion between
		// gear ↔ help). Chain the new open off the closing trigger's
		// aria-expanded flip rather than a magic-number setTimeout — wp-admin's
		// screen-meta.js sets aria-expanded synchronously when its handler fires,
		// so the observer fires as soon as the close has registered, regardless
		// of however long the slideUp animation takes. Self-disconnects on first
		// flip so back-to-back clicks don't accumulate observers.
		const otherTriggerId =
			which === 'screen-options'
				? '#contextual-help-link'
				: '#show-settings-link';
		const otherOpen = document.querySelector< HTMLButtonElement >(
			`${ otherTriggerId }[aria-expanded="true"]`
		);
		const openTarget = () =>
			document.querySelector< HTMLButtonElement >( triggerId )?.click();
		if ( otherOpen ) {
			const chain = new MutationObserver( () => {
				if ( otherOpen.getAttribute( 'aria-expanded' ) !== 'true' ) {
					chain.disconnect();
					openTarget();
				}
			} );
			chain.observe( otherOpen, {
				attributes: true,
				attributeFilter: [ 'aria-expanded' ],
			} );
			otherOpen.click();
		} else {
			openTarget();
		}
	};

	return {
		hasH1,
		hasScreenOptions,
		hasContextualHelp,
		activeMetaIcon,
		triggerMetaIcon,
	};
};
