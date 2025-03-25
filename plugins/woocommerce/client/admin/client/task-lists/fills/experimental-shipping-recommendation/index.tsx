/**
 * External dependencies
 */
import { optionsStore, pluginsStore, settingsStore } from '@woocommerce/data';
import { withSelect } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { compose } from '@wordpress/compose';
import type { SelectFunction } from '@wordpress/data/build-types/types';
/**
 * Internal dependencies
 */
import { ShippingRecommendation } from './shipping-recommendation';
import { TaskProps } from './types';

const ShippingRecommendationWrapper = compose(
	withSelect( ( select: SelectFunction ) => {
		const { getSettings } = select( settingsStore );
		const { hasFinishedResolution } = select( optionsStore );
		const { getActivePlugins } = select( pluginsStore );

		return {
			activePlugins: getActivePlugins(),
			generalSettings: getSettings( 'general' )?.general,
			isJetpackConnected: select( pluginsStore ).isJetpackConnected(),
			isResolving:
				! hasFinishedResolution( 'getOption', [
					'woocommerce_setup_jetpack_opted_in',
				] ) ||
				! hasFinishedResolution( 'getOption', [
					'wcshipping_options',
				] ) ||
				! select( pluginsStore ).hasFinishedResolution(
					'isJetpackConnected',
					[]
				),
		};
	} )
)( ShippingRecommendation ) as React.ComponentType< TaskProps >;

registerPlugin( 'wc-admin-onboarding-task-shipping-recommendation', {
	scope: 'woocommerce-tasks',
	render: () => (
		<WooOnboardingTask id="shipping-recommendation">
			{ ( { onComplete, query, task } ) => (
				<ShippingRecommendationWrapper
					onComplete={ onComplete }
					query={ query }
					task={ task }
				/>
			) }
		</WooOnboardingTask>
	),
} );
