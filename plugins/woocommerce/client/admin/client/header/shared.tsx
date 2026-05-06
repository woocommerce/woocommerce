/**
 * External dependencies
 */
import {
	useCallback,
	useEffect,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { useSlot, Text } from '@woocommerce/experimental';
import clsx from 'clsx';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { cog, help } from '@wordpress/icons';
import {
	WC_HEADER_SLOT_NAME,
	WC_HEADER_PAGE_TITLE_SLOT_NAME,
	WooHeaderNavigationItem,
	WooHeaderItem,
	WooHeaderPageTitle,
} from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { TasksReminderBar } from '../task-lists/reminder-bar';
import useIsScrolled from '~/hooks/useIsScrolled';

export const useUpdateBodyMargin = ( {
	headerElement,
	headerItemSlot,
}: {
	headerElement: React.RefObject< HTMLDivElement >;
	headerItemSlot: ReturnType< typeof useSlot >;
} ) => {
	const debounceTimer = useRef< NodeJS.Timeout | null >( null );

	const updateBodyMargin = useCallback( () => {
		if ( debounceTimer.current ) {
			clearTimeout( debounceTimer.current );
		}

		debounceTimer.current = setTimeout( function () {
			const wpBody =
				document.querySelector< HTMLDivElement >( '#wpbody' );

			if ( ! wpBody || ! headerElement.current ) {
				return;
			}

			wpBody.style.marginTop = `${ headerElement.current.clientHeight }px`;
		}, 200 );
	}, [ headerElement ] );

	useLayoutEffect( () => {
		updateBodyMargin();
		window.addEventListener( 'resize', updateBodyMargin );
		return () => {
			window.removeEventListener( 'resize', updateBodyMargin );
			const wpBody =
				document.querySelector< HTMLDivElement >( '#wpbody' );

			if ( ! wpBody ) {
				return;
			}

			wpBody.style.marginTop = '';
		};
	}, [ headerItemSlot?.fills, updateBodyMargin ] );

	return updateBodyMargin;
};

export const getPageTitle = ( sections: string[] ) => {
	let pageTitle;
	const pagesWithTabs = [
		'admin.php?page=wc-settings',
		'admin.php?page=wc-reports',
		'admin.php?page=wc-status',
	];

	if (
		sections.length > 2 &&
		Array.isArray( sections[ 1 ] ) &&
		pagesWithTabs.includes( sections[ 1 ][ 0 ] )
	) {
		pageTitle = sections[ 1 ][ 1 ];
	} else {
		pageTitle = sections[ sections.length - 1 ];
	}
	return pageTitle;
};

export const BaseHeader = ( {
	isEmbedded,
	query,
	showReminderBar,
	sections,
	children,
	leftAlign = true,
}: {
	isEmbedded: boolean;
	query: Record< string, string >;
	showReminderBar: boolean;
	sections: string[];
	children?: React.ReactNode;
	leftAlign?: boolean;
} ) => {
	const { isScrolled } = useIsScrolled();

	const headerElement = useRef< HTMLDivElement >( null );
	const pageTitleSlot = useSlot( WC_HEADER_PAGE_TITLE_SLOT_NAME );
	const hasPageTitleFills = Boolean( pageTitleSlot?.fills?.length );
	const headerItemSlot = useSlot( WC_HEADER_SLOT_NAME );
	const updateBodyMargin = useUpdateBodyMargin( {
		headerElement,
		headerItemSlot,
	} );

	// On embedded pages, suppress the floating-header <h1> only when wp-admin
	// actually rendered its own <h1> (post-type screens like Edit Product / Edit
	// Order). On Woo-custom admin pages (Settings, etc.) wp-admin doesn't render
	// an h1, so the floating header is the only title and must stay.
	//
	// Lazy initial state reads the DOM synchronously on first render so we
	// don't get a one-frame duplicate-title flash before useEffect commits.
	// wp-admin's <h1> + meta-link wraps are server-rendered before React
	// hydrates, so this is safe. The useEffect below still re-runs on `query`
	// changes if BaseHeader persists across client-side route transitions.
	const [ hasWpAdminH1, setHasWpAdminH1 ] = useState( () =>
		typeof document !== 'undefined'
			? !! document.querySelector( '.wrap > h1.wp-heading-inline' )
			: false
	);
	const [ hasScreenOptions, setHasScreenOptions ] = useState( () =>
		typeof document !== 'undefined'
			? !! document.querySelector( '#screen-options-link-wrap' )
			: false
	);
	const [ hasContextualHelp, setHasContextualHelp ] = useState( () =>
		typeof document !== 'undefined'
			? !! document.querySelector( '#contextual-help-link-wrap' )
			: false
	);
	useEffect( () => {
		setHasWpAdminH1(
			!! document.querySelector( '.wrap > h1.wp-heading-inline' )
		);
		setHasScreenOptions(
			!! document.querySelector( '#screen-options-link-wrap' )
		);
		setHasContextualHelp(
			!! document.querySelector( '#contextual-help-link-wrap' )
		);
	}, [ query ] );

	// Track which meta-icon dropdown is currently active so we can render the
	// blue-underline active state (mirroring the activity-panel tab pattern).
	// Only one is active at a time — opening either closes any open
	// activity-panel tab first, keeping all four icons behaving as one tab group.
	const [ activeMetaIcon, setActiveMetaIcon ] = useState<
		'screen-options' | 'help' | null
	>( null );

	// Reverse direction of the tab-group sync: when an activity-panel tab is
	// clicked AND a wp-admin dropdown is currently open, close the dropdown.
	// We don't update React state from this handler (state syncs reactively
	// via the MutationObserver below), so no setTimeout deferral is needed.
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
	// observing aria-expanded changes on the trigger buttons. This way React
	// state updates only happen *after* a click has fully settled — never during.
	useEffect( () => {
		if ( ! hasScreenOptions && ! hasContextualHelp ) {
			setActiveMetaIcon( null );
			return;
		}
		const screenOptBtn =
			document.querySelector< HTMLButtonElement >( '#show-settings-link' );
		const helpBtn = document.querySelector< HTMLButtonElement >(
			'#contextual-help-link'
		);
		const sync = () => {
			const screenOpen =
				screenOptBtn?.getAttribute( 'aria-expanded' ) === 'true';
			const helpOpen =
				helpBtn?.getAttribute( 'aria-expanded' ) === 'true';
			setActiveMetaIcon(
				screenOpen ? 'screen-options' : helpOpen ? 'help' : null
			);
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
		which: 'screen-options' | 'help',
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

	const shouldRenderTitle =
		! isEmbedded || hasPageTitleFills || ! hasWpAdminH1;

	return (
		<div
			className={ clsx( 'woocommerce-layout__header', {
				'is-scrolled': isScrolled,
				// Chrome-only treatment: bar collapses to admin-bar height when
				// no title is rendered (Edit Order, Edit Product, Add Product).
				'is-chrome-only': ! shouldRenderTitle,
			} ) }
			ref={ headerElement }
		>
			{ showReminderBar && (
				<TasksReminderBar
					updateBodyMargin={ updateBodyMargin }
					taskListId="setup"
				/>
			) }
			<div className="woocommerce-layout__header-wrapper">
				<WooHeaderNavigationItem.Slot
					fillProps={ { isEmbedded, query } }
				/>

				{ shouldRenderTitle ? (
					<Text
						className={ clsx(
							'woocommerce-layout__header-heading',
							{
								'woocommerce-layout__header-left-align':
									leftAlign,
							}
						) }
						as="h1"
					>
						{ hasPageTitleFills ? (
							<WooHeaderPageTitle.Slot
								fillProps={ { isEmbedded, query } }
							/>
						) : (
							decodeEntities( getPageTitle( sections ) )
						) }
					</Text>
				) : (
					// Spacer keeps WooHeaderItem.Slot pinned right when no title renders.
					<div
						className="woocommerce-layout__header-spacer"
						aria-hidden="true"
					/>
				) }

				{ children }
				<WooHeaderItem.Slot fillProps={ { isEmbedded, query } } />

				{ /* Screen Options + Help icons consolidated into the floating
				header. Only rendered when wp-admin would have rendered the
				corresponding entry point (Settings, e.g., does not). The
				original wp-admin wraps are visually hidden via CSS and these
				icons proxy clicks into them through triggerMetaIcon. */ }
				{ hasScreenOptions && (
					<Button
						className={ clsx(
							'woocommerce-layout__header-meta-icon',
							{
								'is-active':
									activeMetaIcon === 'screen-options',
							}
						) }
						label={ __( 'Screen options', 'woocommerce' ) }
						aria-expanded={
							activeMetaIcon === 'screen-options'
						}
						showTooltip
						onClick={ () =>
							triggerMetaIcon(
								'screen-options',
								'#show-settings-link'
							)
						}
					>
						<Icon icon={ cog } size={ 18 } />
					</Button>
				) }
				{ hasContextualHelp && (
					<Button
						className={ clsx(
							'woocommerce-layout__header-meta-icon',
							{
								'is-active': activeMetaIcon === 'help',
							}
						) }
						label={ __( 'Help', 'woocommerce' ) }
						aria-expanded={ activeMetaIcon === 'help' }
						showTooltip
						onClick={ () =>
							triggerMetaIcon(
								'help',
								'#contextual-help-link'
							)
						}
					>
						<Icon icon={ help } size={ 18 } />
					</Button>
				) }
			</div>
		</div>
	);
};
