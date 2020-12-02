/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	WooNavigationItem,
	getNewPath,
	getPersistedQuery,
} from '@woocommerce/navigation';
import { Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import getReports from '../analytics/report/get-reports';
import { getPages } from './controller';
import { isWCAdmin } from '../dashboard/utils';

const NavigationPlugin = () => {
	/**
	 * If the current page is embedded, stay with the default urls
	 * provided by Navigation because the router isn't present to
	 * respond to <Link /> component's manipulation of the url.
	 */
	if ( ! isWCAdmin( window.location.href ) ) {
		return null;
	}
	const reports = getReports().filter( ( item ) => item.navArgs );
	const pages = getPages()
		.filter( ( page ) => page.navArgs )
		.map( ( page ) => {
			if ( page.path === '/analytics/settings' ) {
				return {
					...page,
					breadcrumbs: [ __( 'Analytics', 'woocommerce-admin' ) ],
				};
			}
			return page;
		} );
	const persistedQuery = getPersistedQuery( {} );
	return (
		<>
			{ pages.map( ( page ) => (
				<WooNavigationItem
					item={ page.navArgs.id }
					key={ page.navArgs.id }
				>
					<Link
						className="components-button"
						href={ getNewPath( persistedQuery, page.path, {} ) }
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
							persistedQuery,
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

registerPlugin( 'wc-admin-navigation', { render: NavigationPlugin } );
