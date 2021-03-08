/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { Spinner } from '@woocommerce/components';
import { ONBOARDING_STORE_NAME, SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { BusinessDetailsStepWithExtensionList } from './flows/bundle';
import { SelectiveFeaturesBusinessStep } from './flows/selective-bundle';
import './style.scss';
import { isSelectiveBundleInstallSegmentation } from './data/segmentation';

export const BusinessDetailsStep = ( props ) => {
	const { profileItems, settings, isLoading } = useSelect( ( select ) => {
		return {
			isLoading:
				! select( ONBOARDING_STORE_NAME ).hasFinishedResolution(
					'getProfileItems'
				) ||
				! select(
					SETTINGS_STORE_NAME
				).hasFinishedResolution( 'getSettings', [ 'general' ] ),
			profileItems: select( ONBOARDING_STORE_NAME ).getProfileItems(),
			settings:
				select( SETTINGS_STORE_NAME ).getSettings( 'general' ) || {},
		};
	} );

	const country = settings.general
		? settings.general.woocommerce_default_country
		: null;

	const selectiveBundleInstallSegmentation = isSelectiveBundleInstallSegmentation(
		country
	);

	if ( isLoading ) {
		return (
			<div className="woocommerce-admin__business-details__spinner">
				<Spinner />
			</div>
		);
	}

	if ( selectiveBundleInstallSegmentation ) {
		const initialValues = {
			other_platform: profileItems.other_platform || '',
			other_platform_name: profileItems.other_platform_name || '',
			product_count: profileItems.product_count || '',
			selling_venues: profileItems.selling_venues || '',
			revenue: profileItems.revenue || '',
		};

		return (
			<SelectiveFeaturesBusinessStep
				{ ...props }
				initialValues={ initialValues }
			/>
		);
	}

	return <BusinessDetailsStepWithExtensionList { ...props } />;
};
