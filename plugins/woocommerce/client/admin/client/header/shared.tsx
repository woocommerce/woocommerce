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
	// an h1, so the floating header is the only title and must stay. Detect at
	// mount; the wp-admin <h1> is server-rendered and present before React hydrates.
	const [ hasWpAdminH1, setHasWpAdminH1 ] = useState( false );
	// Detect wp-admin's Screen Options + Help button wraps so we only render
	// the corresponding placeholder icons when the underlying entry points exist.
	// (Settings removes help tabs in PHP; Woo-React-only pages may not have them.)
	const [ hasScreenOptions, setHasScreenOptions ] = useState( false );
	const [ hasContextualHelp, setHasContextualHelp ] = useState( false );
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
	}, [] );

	// Track which meta-icon dropdown is currently active so we can render the
	// blue-underline active state (mirroring the activity-panel tab pattern).
	// Only one is active at a time — opening either closes any open
	// activity-panel tab first, keeping all four icons behaving as one tab group.
	const [ activeMetaIcon, setActiveMetaIcon ] = useState<
		'screen-options' | 'help' | null
	>( null );

	// Reverse direction of the tab-group sync: when an activity-panel tab is
	// clicked AND a wp-admin dropdown is currently open, close the dropdown and
	// clear our active state. Bail out early when there's nothing to close —
	// otherwise we'd trigger a no-op setState mid-click and re-render BaseHeader,
	// which destabilises the activity panel's own tab-switch state and breaks
	// switching between two activity-panel tabs (e.g. bell ↔ finish setup).
	useEffect( () => {
		const handler = ( e: Event ) => {
			const target = e.target as HTMLElement | null;
			if (
				! target?.closest( '.woocommerce-layout__activity-panel-tab' )
			) {
				return;
			}
			const screenOptOpen =
				document.querySelector< HTMLButtonElement >(
					'#show-settings-link[aria-expanded="true"]'
				);
			const helpOpen = document.querySelector< HTMLButtonElement >(
				'#contextual-help-link[aria-expanded="true"]'
			);
			if ( ! screenOptOpen && ! helpOpen ) {
				return;
			}
			screenOptOpen?.click();
			helpOpen?.click();
			setActiveMetaIcon( null );
		};
		document.addEventListener( 'click', handler, true );
		return () => document.removeEventListener( 'click', handler, true );
	}, [] );

	const triggerMetaIcon = (
		which: 'screen-options' | 'help',
		triggerId: string
	) => {
		// Close any open activity-panel tab so the four icons act as one group.
		document
			.querySelector< HTMLButtonElement >(
				'.woocommerce-layout__activity-panel-tab.is-active'
			)
			?.click();
		const trigger =
			document.querySelector< HTMLButtonElement >( triggerId );
		trigger?.click();
		// wp-admin's screen-meta.js sets aria-expanded synchronously after click.
		const isOpen = trigger?.getAttribute( 'aria-expanded' ) === 'true';
		setActiveMetaIcon( isOpen ? which : null );
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
						{ decodeEntities(
							hasPageTitleFills ? (
								<WooHeaderPageTitle.Slot
									fillProps={ { isEmbedded, query } }
								/>
							) : (
								getPageTitle( sections )
							)
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

				{ /* Placeholder Screen Options + Help icons consolidated into
				the floating header. Only rendered when wp-admin would have
				rendered the corresponding entry point (Settings, e.g., does
				not). wp-admin's original strip is hidden via CSS. Buttons are
				non-functional placeholders for the design prototype. */ }
				{ hasScreenOptions && (
					<Button
						className={ clsx(
							'woocommerce-layout__header-meta-icon',
							{
								'is-active':
									activeMetaIcon === 'screen-options',
							}
						) }
						aria-label={ __(
							'Screen Options',
							'woocommerce'
						) }
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
						aria-label={ __( 'Help', 'woocommerce' ) }
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
