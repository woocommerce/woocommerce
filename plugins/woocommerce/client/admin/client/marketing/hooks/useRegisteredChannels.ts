/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RegisteredChannel, SyncStatusType } from '~/marketing/types';
import { STORE_KEY } from '~/marketing/data-multichannel/constants';
import {
	ApiFetchError,
	RegisteredChannel as APIRegisteredChannel,
	RegisteredChannelsState,
} from '~/marketing/data-multichannel/types';

type UseRegisteredChannels = {
	loading: boolean;
	data?: Array< RegisteredChannel >;
	error?: ApiFetchError;
	refetch: () => void;
};

/**
 * An object that maps the product listings status in
 * plugins/woocommerce/src/Admin/Marketing/MarketingChannelInterface.php backend
 * to SyncStatusType frontend.
 */
const statusMap: Record< string, SyncStatusType > = {
	'sync-in-progress': 'syncing',
	'sync-failed': 'failed',
	synced: 'synced',
};

const convert = ( data: APIRegisteredChannel ): RegisteredChannel => {
	const issueType = data.errors_count >= 1 ? 'error' : 'none';
	const issueText =
		data.errors_count >= 1
			? sprintf(
					// translators: %d: The number of issues to resolve.
					__( '%d issues to resolve', 'woocommerce' ),
					data.errors_count
			  )
			: __( 'No issues to resolve', 'woocommerce' );

	return {
		slug: data.slug,
		title: data.name,
		description: data.description,
		icon: data.icon,
		isSetupCompleted: data.is_setup_completed,
		setupUrl: data.settings_url,
		manageUrl: data.settings_url,
		syncStatus: statusMap[ data.product_listings_status ],
		issueType,
		issueText,
	};
};

export const useRegisteredChannels = (): UseRegisteredChannels => {
	const { invalidateResolution } = useDispatch( STORE_KEY );

	const refetch = useCallback( () => {
		invalidateResolution( 'getRegisteredChannels', [] );
	}, [ invalidateResolution ] );

	return useSelect( ( select ) => {
		const { hasFinishedResolution, getRegisteredChannels } =
			select( STORE_KEY );
		const state = getRegisteredChannels< RegisteredChannelsState >();

		return {
			loading: ! hasFinishedResolution( 'getRegisteredChannels', [] ),
			data: state.data?.map( convert ),
			error: state.error,
			refetch,
		};
	} );
};
