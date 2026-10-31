/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { pluginsStore } from '@woocommerce/data';
import { differenceWith } from 'lodash';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/marketing/data-multichannel/constants';
import type { RecommendedChannel } from '~/marketing/data-multichannel/types';
import { getAdminSetting } from '~/utils/admin-settings';

type UseRecommendedChannels = {
	loading: boolean;
	data?: Array< RecommendedChannel >;
};

// Comes from PHP because the REST user capabilities report install_plugins
// for site admins on multisite, even though they can't install plugins.
const { canInstallPlugins = true } = getAdminSetting( 'marketing', {} );

export const useRecommendedChannels = (): UseRecommendedChannels => {
	return useSelect( ( select ) => {
		if ( ! canInstallPlugins ) {
			return { loading: false, data: [] };
		}

		const { hasFinishedResolution, getRecommendedChannels } =
			select( STORE_KEY );
		const { data, error } = getRecommendedChannels() as {
			data?: RecommendedChannel[];
			error?: unknown;
		};

		const { getActivePlugins } = select( pluginsStore );
		const activePlugins = getActivePlugins();

		/**
		 * Recommended channels that are not in "active" state,
		 * i.e. channels that are not installed or not activated yet.
		 */
		const nonActiveRecommendedChannels =
			data &&
			differenceWith( data, activePlugins, ( a, b ) => {
				return a.product === b;
			} );

		return {
			loading: ! hasFinishedResolution( 'getRecommendedChannels', [] ),
			data: nonActiveRecommendedChannels,
			error,
		};
	}, [] );
};
