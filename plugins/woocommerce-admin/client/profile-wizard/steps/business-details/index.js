/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { Spinner } from '@woocommerce/components';
import { ONBOARDING_STORE_NAME, SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { BusinessFeaturesList } from './flows/selective-bundle';
import './style.scss';

export const BusinessDetailsStep = ( props ) => {
	const { profileItems, isLoading } = useSelect( ( select ) => {
		return {
			isLoading:
				! select( ONBOARDING_STORE_NAME ).hasFinishedResolution(
					'getProfileItems'
				) ||
				! select(
					SETTINGS_STORE_NAME
				).hasFinishedResolution( 'getSettings', [ 'general' ] ),
			profileItems: select( ONBOARDING_STORE_NAME ).getProfileItems(),
		};
	} );

	if ( isLoading ) {
		return (
			<div className="woocommerce-admin__business-details__spinner">
				<Spinner />
			</div>
		);
	}

	const initialValues = {
		other_platform: profileItems.other_platform || '',
		other_platform_name: profileItems.other_platform_name || '',
		product_count: profileItems.product_count || '',
		selling_venues: profileItems.selling_venues || '',
		revenue: profileItems.revenue || '',
		setup_client: profileItems.setup_client || false,
	};

	return (
		<BusinessFeaturesList { ...props } initialValues={ initialValues } />
	);
};
