/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useLayoutEffect, useRef } from '@wordpress/element';
import clsx from 'clsx';
import { decodeEntities } from '@wordpress/html-entities';
import {
	WC_HEADER_SLOT_NAME,
	WC_HEADER_PAGE_TITLE_SLOT_NAME,
	WooHeaderNavigationItem,
	WooHeaderItem,
	WooHeaderPageTitle,
} from '@woocommerce/admin-layout';
import { getSetting } from '@woocommerce/settings';
import { Text, useSlot } from '@woocommerce/experimental';
import { getScreenFromPath, isWCAdmin } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import './style.scss';
import useIsScrolled from '../hooks/useIsScrolled';
import { TasksReminderBar, useActiveSetupTasklist } from '../task-lists';
import {
	LaunchYourStoreStatus,
	useLaunchYourStore,
} from '../launch-your-store';

export const PAGE_TITLE_FILTER = 'woocommerce_admin_header_page_title';

export const getPageTitle = ( sections ) => {
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

export const Header = ( { sections, isEmbedded = false, query } ) => {
	const headerElement = useRef( null );
	const activeSetupList = useActiveSetupTasklist();
	const siteTitle = getSetting( 'siteTitle', '' );
	const pageTitle = getPageTitle( sections );
	const { isScrolled } = useIsScrolled();
	let debounceTimer = null;
	const className = clsx( 'woocommerce-layout__header', {
		'is-scrolled': isScrolled,
	} );

	const pageTitleSlot = useSlot( WC_HEADER_PAGE_TITLE_SLOT_NAME );
	const hasPageTitleFills = Boolean( pageTitleSlot?.fills?.length );
	const headerItemSlot = useSlot( WC_HEADER_SLOT_NAME );
	const headerItemSlotFills = headerItemSlot?.fills;

	const updateBodyMargin = () => {
		clearTimeout( debounceTimer );
		debounceTimer = setTimeout( function () {
			const wpBody = document.querySelector( '#wpbody' );

			if ( ! wpBody || ! headerElement.current ) {
				return;
			}

			wpBody.style.marginTop = `${ headerElement.current.clientHeight }px`;
		}, 200 );
	};

	useLayoutEffect( () => {
		updateBodyMargin();
		window.addEventListener( 'resize', updateBodyMargin );
		return () => {
			window.removeEventListener( 'resize', updateBodyMargin );
			const wpBody = document.querySelector( '#wpbody' );

			if ( ! wpBody ) {
				return;
			}

			wpBody.style.marginTop = null;
		};
	}, [ headerItemSlotFills ] );

	useEffect( () => {
		if ( ! isEmbedded ) {
			const documentTitle = sections
				.map( ( section ) => {
					return Array.isArray( section ) ? section[ 1 ] : section;
				} )
				.reverse()
				.join( ' &lsaquo; ' );

			const decodedTitle = decodeEntities(
				sprintf(
					/* translators: 1: document title. 2: page title */
					__(
						'%1$s &lsaquo; %2$s &#8212; WooCommerce',
						'woocommerce'
					),
					documentTitle,
					siteTitle
				)
			);

			if ( document.title !== decodedTitle ) {
				document.title = decodedTitle;
			}
		}
	}, [ isEmbedded, sections, siteTitle ] );

	const isHomescreen =
		isWCAdmin() && getScreenFromPath() === 'homescreen' && ! query.task;
	const { isLoading, launchYourStoreEnabled } = useLaunchYourStore( {
		enabled: isHomescreen,
	} );
	const showLaunchYourStoreStatus =
		isHomescreen && launchYourStoreEnabled && ! isLoading;

	return (
		<div className={ className } ref={ headerElement }>
			{ activeSetupList && (
				<TasksReminderBar
					updateBodyMargin={ updateBodyMargin }
					taskListId={ activeSetupList }
				/>
			) }
			<div className="woocommerce-layout__header-wrapper">
				<WooHeaderNavigationItem.Slot
					fillProps={ { isEmbedded, query } }
				/>

				<Text
					className={ `woocommerce-layout__header-heading ${
						showLaunchYourStoreStatus
							? ''
							: 'woocommerce-layout__header-left-align'
					}` }
					as="h1"
				>
					{ decodeEntities(
						hasPageTitleFills ? (
							<WooHeaderPageTitle.Slot
								fillProps={ { isEmbedded, query } }
							/>
						) : (
							pageTitle
						)
					) }
				</Text>

				{ showLaunchYourStoreStatus && <LaunchYourStoreStatus /> }

				<WooHeaderItem.Slot fillProps={ { isEmbedded, query } } />
			</div>
		</div>
	);
};
