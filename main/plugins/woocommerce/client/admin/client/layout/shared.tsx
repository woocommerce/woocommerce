/**
 * External dependencies
 */
import '@woocommerce/notices';
import { lazy, Suspense } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Notices from './notices';

const StoreAlerts = lazy(
	() => import( /* webpackChunkName: "store-alerts" */ './store-alerts' )
);

export const PrimaryLayout = ( {
	children,
	showStoreAlerts = true,
	showNotices = true,
}: {
	children?: React.ReactNode;
	showStoreAlerts?: boolean;
	showNotices?: boolean;
} ) => {
	return (
		<div
			className="woocommerce-layout__primary"
			id="woocommerce-layout__primary"
		>
			{ window.wcAdminFeatures[ 'store-alerts' ] && showStoreAlerts && (
				<Suspense fallback={ null }>
					<StoreAlerts />
				</Suspense>
			) }
			{ showNotices && <Notices /> }
			{ children }
		</div>
	);
};
