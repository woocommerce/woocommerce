/**
 * External dependencies
 */
import clsx from 'clsx';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { FormStep } from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { checkoutStore as checkoutStoreDescriptor } from '@woocommerce/block-data';
import { LOCAL_PICKUP_ENABLED } from '@woocommerce/block-settings';
import { useCheckoutBlockContext } from '@woocommerce/blocks/checkout/context';
import { useShippingData } from '@woocommerce/base-context/hooks';
import { isPackageRateCollectable } from '@woocommerce/base-utils';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';

const DEFAULT_PICKUP_OPTIONS_TITLE = __( 'Pickup locations', 'woocommerce' );

export const getPickupOptionsTitle = (
	title: string,
	pickupLocationsCount: number
) => {
	if (
		title === DEFAULT_PICKUP_OPTIONS_TITLE &&
		pickupLocationsCount === 1
	) {
		return __( 'Pickup location', 'woocommerce' );
	}

	return title;
};

const FrontendBlock = ( {
	title,
	description,
	children,
	className,
}: {
	title: string;
	description: string;
	showStepNumber: boolean;
	children: JSX.Element;
	className?: string;
} ) => {
	const { checkoutIsProcessing, prefersCollection } = useSelect(
		( select ) => {
			const checkoutStore = select( checkoutStoreDescriptor );
			return {
				checkoutIsProcessing: checkoutStore.isProcessing(),
				prefersCollection: checkoutStore.prefersCollection(),
			};
		}
	);

	const { showFormStepNumbers } = useCheckoutBlockContext();
	const { shippingRates } = useShippingData();
	const pickupLocationsCount = (
		shippingRates[ 0 ]?.shipping_rates || []
	).filter( isPackageRateCollectable ).length;

	if ( ! prefersCollection || ! LOCAL_PICKUP_ENABLED ) {
		return null;
	}

	return (
		<FormStep
			id="pickup-options"
			disabled={ checkoutIsProcessing }
			className={ clsx( 'wc-block-checkout__pickup-options', className ) }
			title={ getPickupOptionsTitle( title, pickupLocationsCount ) }
			description={ description }
			showStepNumber={ showFormStepNumbers }
		>
			<Block />
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
