/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import ActivityPanel from './activity-panel';
import { WooHeaderItem } from '~/header/utils';

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
