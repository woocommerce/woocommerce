/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { withPluginsHydration, withOptionsHydration } from '@woocommerce/data';
import '@woocommerce/notices';
import { identity, isFunction } from 'lodash';
import { SlotFillProvider } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { CustomerEffortScoreModalContainer } from '@woocommerce/customer-effort-score';
import { getQuery } from '@woocommerce/navigation';
import { recordPageView } from '@woocommerce/tracks';
import {
	LayoutContextProvider,
	getLayoutContextValue,
} from '@woocommerce/admin-layout';
import { PluginArea } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import './style.scss';
import '~/activity-panel';
import { EmbedHeader } from '../header/embed';
import { TransientNotices } from './transient-notices';
import { usePageClasses } from './hooks/use-page-classes';
import { getAdminSetting } from '~/utils/admin-settings';
import { Footer } from './footer';

const dataEndpoints = getAdminSetting( 'dataEndpoints' );

export const _EmbedLayout = () => {
	const breadcrumbs = getAdminSetting( 'embedBreadcrumbs', [] );

	usePageClasses( {
		breadcrumbs,
	} );

	useEffect( () => {
		const path = document.location.pathname + document.location.search;
		recordPageView( path, {
			is_embedded: true,
		} );
	}, [] );

	const query = getQuery() as Record< string, string >;

	return (
		<LayoutContextProvider value={ getLayoutContextValue( [ 'page' ] ) }>
			<SlotFillProvider>
				<div className="woocommerce-layout">
					<EmbedHeader
						sections={
							isFunction( breadcrumbs )
								? breadcrumbs( {} )
								: breadcrumbs
						}
						query={ query }
					/>
					<TransientNotices />
					<Footer />
					<CustomerEffortScoreModalContainer />
				</div>

				<PluginArea scope="woocommerce-admin" />
			</SlotFillProvider>
		</LayoutContextProvider>
	);
};

export const EmbedLayout = compose(
	getAdminSetting( 'preloadOptions' )
		? withOptionsHydration( {
				...getAdminSetting( 'preloadOptions' ),
		  } )
		: identity,
	withPluginsHydration( {
		...getAdminSetting( 'plugins', {} ),
		jetpackStatus:
			( dataEndpoints && dataEndpoints.jetpackStatus ) || false,
	} )
)( _EmbedLayout ) as React.ComponentType< Record< string, unknown > >;
