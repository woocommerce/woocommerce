/**
 * External dependencies
 */
import { createPortal, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { UpdateBanner } from './update-banner';
import { useUpdateBanner } from './hooks/use-update-banner';

/**
 * DOM selectors we try in order to find the editor canvas to portal
 * the banner into. The first match wins; if none match we fall back
 * to `document.body` so the banner never silently disappears.
 */
const PORTAL_SELECTORS = [
	'.edit-post-visual-editor',
	'.editor-canvas-container',
	'.wp-block-post-content',
];

/**
 * Walk the selector list and return the first matching element, or
 * `null` if nothing matches. Pure DOM lookup — no React state.
 */
function resolvePortalTarget(): HTMLElement | null {
	for ( const selector of PORTAL_SELECTORS ) {
		const node = document.querySelector( selector );
		if ( node instanceof HTMLElement ) {
			return node;
		}
	}
	return null;
}

/**
 * Mounts the `<UpdateBanner>` into the email editor's canvas via a
 * portal (RSM-141). Pure orchestration: reads the banner state from
 * `useUpdateBanner` and forwards it to the presentational component.
 *
 * Resolves the portal target on mount AND once more on the next
 * animation frame, since the canvas can mount on a later paint than
 * the plugin itself. Falls back to `document.body` if no canvas
 * selector matches — the banner never silently disappears.
 */
export function UpdateBannerPlugin(): JSX.Element | null {
	const banner = useUpdateBanner();
	const [ portalTarget, setPortalTarget ] = useState< HTMLElement | null >(
		() => resolvePortalTarget() ?? document.body
	);

	useEffect( () => {
		// First-paint resolution may have run before the canvas
		// mounted; re-resolve once on the next frame to catch up.
		const handle = window.requestAnimationFrame( () => {
			const next = resolvePortalTarget() ?? document.body;
			setPortalTarget( ( prev ) => ( prev === next ? prev : next ) );
		} );
		return () => {
			window.cancelAnimationFrame( handle );
		};
	}, [] );

	if ( ! banner.shouldRender || ! portalTarget ) {
		return null;
	}

	return createPortal(
		<UpdateBanner
			summary={ banner.summary }
			applyState={ banner.applyState }
			canApply={ banner.canApply }
			canReview={ banner.canReview }
			disabledReason={ banner.disabledReason }
			expanded={ banner.expanded }
			onApply={ banner.apply }
			onReview={ banner.openReview }
			onDismiss={ banner.dismiss }
			onAutoDismiss={ banner.autoDismiss }
			onToggleExpanded={ banner.toggleExpanded }
		/>,
		portalTarget
	);
}
