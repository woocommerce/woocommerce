/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { WooHeaderItem } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import ActivityPanel from './activity-panel';

const ActivityPanelHeaderItem = () => (
	<WooHeaderItem order={ 20 }>
		{ ( { isEmbedded, query } ) => {
			if ( ! window.wcAdminFeatures[ 'activity-panels' ] ) {
				return null;
			}

			return <ActivityPanel isEmbedded={ isEmbedded } query={ query } />;
		} }
	</WooHeaderItem>
);

registerPlugin( 'activity-panel-header-item', {
	render: ActivityPanelHeaderItem,
	scope: 'woocommerce-admin',
} );
