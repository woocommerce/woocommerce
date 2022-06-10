/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	WooNavigationItem,
	getNewPath,
	pathIsExcluded,
	isWCAdmin,
} from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { NAVIGATION_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import getReports from '../analytics/report/get-reports';
import { getPages } from './controller';
import Navigation from '~/navigation';
import { WooHeaderNavigationItem } from '~/header/utils';

const NavigationPlugin = () => {
	const { persistedQuery } = useSelect( ( select ) => {
		return {
			persistedQuery: select( NAVIGATION_STORE_NAME ).getPersistedQuery(),
		};
	} );

	if ( ! window.wcAdminFeatures.navigation ) {
		return null;
	}

	/**
	 * If the current page is embedded, stay with the default urls
	 * provided by Navigation because the router isn't present to
	 * respond to <Link /> component's manipulation of the url.
	 */
	if ( ! isWCAdmin() ) {
		return (
			<WooHeaderNavigationItem order={ -100 }>
				<Navigation />
			</WooHeaderNavigationItem>
		);
	}

	const reports = getReports().filter( ( item ) => item.navArgs );

	const pages = getPages()
		.filter( ( page ) => page.navArgs )
		.map( ( page ) => {
			if ( page.path === '/analytics/settings' ) {
				return {
					...page,
					breadcrumbs: [ __( 'Analytics', 'woocommerce' ) ],
				};
			}
			return page;
		} );

	return (
		<>
			<WooHeaderNavigationItem order={ -100 }>
				<Navigation />
			</WooHeaderNavigationItem>
			{ pages.map( ( page ) => (
				<WooNavigationItem
					item={ page.navArgs.id }
					key={ page.navArgs.id }
				>
					<Link
						className="components-button"
						href={ getNewPath(
							pathIsExcluded( page.path ) ? {} : persistedQuery,
							page.path,
							{}
						) }
						type="wc-admin"
					>
						{ page.breadcrumbs[ page.breadcrumbs.length - 1 ] }
					</Link>
				</WooNavigationItem>
			) ) }
			{ reports.map( ( item ) => (
				<WooNavigationItem
					item={ item.navArgs.id }
					key={ item.navArgs.id }
				>
					<Link
						className="components-button"
						href={ getNewPath(
							pathIsExcluded( item.report ) ? {} : persistedQuery,
							`/analytics/${ item.report }`,
							{}
						) }
						type="wc-admin"
					>
						{ item.title }
					</Link>
				</WooNavigationItem>
			) ) }
		</>
	);
};

registerPlugin( 'wc-admin-navigation', {
	render: NavigationPlugin,
	scope: 'woocommerce-navigation',
} );
