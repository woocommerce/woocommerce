/**
 * External dependencies
 */
import {
	OPTIONS_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { withSelect } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { compose } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { ShippingRecommendation } from './shipping-recommendation';
import { TaskProps } from './types';

const ShippingRecommendationWrapper = compose(
	withSelect( ( select ) => {
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { hasFinishedResolution } = select( OPTIONS_STORE_NAME );
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );

		return {
			activePlugins: getActivePlugins(),
			generalSettings: getSettings( 'general' )?.general,
			isJetpackConnected:
				select( PLUGINS_STORE_NAME ).isJetpackConnected(),
			isResolving:
				! hasFinishedResolution( 'getOption', [
					'woocommerce_setup_jetpack_opted_in',
				] ) ||
				! hasFinishedResolution( 'getOption', [
					'wc_connect_options',
				] ) ||
				! select( PLUGINS_STORE_NAME ).hasFinishedResolution(
					'isJetpackConnected'
				),
		};
	} )
)( ShippingRecommendation );

registerPlugin( 'wc-admin-onboarding-task-shipping-recommendation', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="shipping-recommendation">
			{ ( { onComplete, query, task }: TaskProps ) => (
				<ShippingRecommendationWrapper
					onComplete={ onComplete }
					query={ query }
					task={ task }
				/>
			) }
		</WooOnboardingTask>
	),
} );
