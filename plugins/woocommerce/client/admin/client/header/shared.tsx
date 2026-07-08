/**
 * External dependencies
 */
import { useCallback, useLayoutEffect, useRef } from '@wordpress/element';
import { useSlot, Text } from '@woocommerce/experimental';
import clsx from 'clsx';
import { decodeEntities } from '@wordpress/html-entities';
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

/**
 * BaseHeader is a dumb layout component shared by Header (non-embedded WC
 * admin pages) and EmbedHeader (overlay on top of classic wp-admin pages).
 * It owns the fixed-position bar, body-margin sync, and slot rendering.
 * Anything wp-admin-specific (h1 suppression,
 * compact-bar mode, Screen Options / Help proxy icons) is the caller's
 * responsibility — passed in via `suppressTitle`, `compact`, and the
 * `trailingItems` slot.
 */
export const BaseHeader = ( {
	isEmbedded,
	query,
	sections,
	children,
	leftAlign = true,
	suppressTitle = false,
	compact = false,
	trailingItems,
}: {
	isEmbedded: boolean;
	query: Record< string, string >;
	sections: string[];
	children?: React.ReactNode;
	leftAlign?: boolean;
	/**
	 * When true, render a spacer instead of the title. Caller (EmbedHeader)
	 * sets this on classic post-type screens where wp-admin already renders
	 * its own <h1>. Page-title slot fills always win over this flag — if a
	 * fill is registered, it renders regardless.
	 */
	suppressTitle?: boolean;
	/**
	 * When true, collapse the bar to admin-bar height. Used in tandem with
	 * `suppressTitle` to give the bar a chrome-only treatment.
	 */
	compact?: boolean;
	/**
	 * Items rendered at the right edge, after WooHeaderItem.Slot. EmbedHeader
	 * uses this for the gear / ? icons that proxy clicks into wp-admin's
	 * Screen Options and Help dropdowns.
	 */
	trailingItems?: React.ReactNode;
} ) => {
	const { isScrolled } = useIsScrolled();

	const headerElement = useRef< HTMLDivElement >( null );
	const pageTitleSlot = useSlot( WC_HEADER_PAGE_TITLE_SLOT_NAME );
	const hasPageTitleFills = Boolean( pageTitleSlot?.fills?.length );
	const headerItemSlot = useSlot( WC_HEADER_SLOT_NAME );
	useUpdateBodyMargin( {
		headerElement,
		headerItemSlot,
	} );

	const shouldRenderTitle = hasPageTitleFills || ! suppressTitle;

	return (
		<div
			className={ clsx( 'woocommerce-layout__header', {
				'is-scrolled': isScrolled,
				// Chrome-only treatment: bar collapses to admin-bar height when
				// the caller requests it (e.g. Edit Order, Edit Product, Add
				// Product, where wp-admin renders its own title below).
				'is-chrome-only': compact,
			} ) }
			ref={ headerElement }
		>
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
					// Spacer keeps WooHeaderItem.Slot pinned right when no
					// title renders.
					<div
						className="woocommerce-layout__header-spacer"
						aria-hidden="true"
					/>
				) }

				{ children }
				<WooHeaderItem.Slot fillProps={ { isEmbedded, query } } />
				{ trailingItems }
			</div>
		</div>
	);
};
