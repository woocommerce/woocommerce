/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useCustomerData,
	useShippingData,
} from '@woocommerce/base-context/hooks';
import { ShippingRatesControl } from '@woocommerce/base-components/cart-checkout';
import {
	getShippingRatesPackageCount,
	hasCollectableRate,
	hasAllFieldsForShippingRates,
} from '@woocommerce/base-utils';
import { StoreNoticesContainer } from '@woocommerce/blocks-components';
import { useEditorContext, noticeContexts } from '@woocommerce/base-context';
import NoticeBanner from '@woocommerce/base-components/notice-banner';
import type { ReactElement } from 'react';
import { useMemo } from '@wordpress/element';
import { renderPackageRateOption } from '@woocommerce/utils';
import { SHIPPING_METHOD_SELECTOR_ENABLED } from '@woocommerce/block-settings';

const NoShippingAddressMessage = () => {
	return (
		<p
			role="status"
			aria-live="polite"
			className="wc-block-components-shipping-rates-control__no-shipping-address-message"
		>
			{ __(
				'Enter a shipping address to view shipping options.',
				'woocommerce'
			) }
		</p>
	);
};

const Block = ( {
	noShippingPlaceholder = null,
}: {
	noShippingPlaceholder?: ReactElement | null;
} ) => {
	const { isEditor } = useEditorContext();

	const {
		shippingRates,
		needsShipping,
		isLoadingRates,
		hasCalculatedShipping,
		isCollectable,
	} = useShippingData();

	const { shippingAddress } = useCustomerData();

	const filteredShippingRates = useMemo( () => {
		return SHIPPING_METHOD_SELECTOR_ENABLED && isCollectable
			? shippingRates.map( ( shippingRatesPackage ) => {
					return {
						...shippingRatesPackage,
						shipping_rates:
							shippingRatesPackage.shipping_rates.filter(
								( shippingRatesPackageRate ) =>
									! hasCollectableRate(
										shippingRatesPackageRate.method_id
									)
							),
					};
			  } )
			: shippingRates;
	}, [ shippingRates, isCollectable ] );

	if ( ! needsShipping ) {
		return null;
	}

	const shippingRatesPackageCount =
		getShippingRatesPackageCount( shippingRates );

	if ( ! hasCalculatedShipping && ! shippingRatesPackageCount ) {
		return <NoShippingAddressMessage />;
	}
	const addressComplete = hasAllFieldsForShippingRates( shippingAddress );

	return (
		<>
			<StoreNoticesContainer
				context={ noticeContexts.SHIPPING_METHODS }
			/>
			{ isEditor && ! shippingRatesPackageCount ? (
				noShippingPlaceholder
			) : (
				<ShippingRatesControl
					noResultsMessage={
						<>
							{ addressComplete ? (
								<NoticeBanner
									isDismissible={ false }
									className="wc-block-components-shipping-rates-control__no-results-notice"
									status="warning"
								>
									{ __(
										'No shipping options are available for this address. Please verify the address is correct or try a different address.',
										'woocommerce'
									) }
								</NoticeBanner>
							) : (
								<NoShippingAddressMessage />
							) }
						</>
					}
					renderOption={ renderPackageRateOption }
					collapsible={ false }
					shippingRates={ filteredShippingRates }
					isLoadingRates={ isLoadingRates }
					context="woocommerce/checkout"
				/>
			) }
		</>
	);
};

export default Block;
