/**
 * External dependencies
 */
import clsx from 'clsx';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { FormStep } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	checkoutStore as checkoutStoreDescriptor,
	cartStore,
} from '@woocommerce/block-data';
import { useShippingData } from '@woocommerce/base-context/hooks';
import { hasCollectableRate } from '@woocommerce/base-utils';
import {
	LOCAL_PICKUP_ENABLED,
	SHIPPING_METHODS_EXIST,
	SHIPPING_ENABLED,
} from '@woocommerce/block-settings';
import { useCheckoutBlockContext } from '@woocommerce/blocks/checkout/context';
import { flushSync, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Block from './block';
import { startCheckoutLayoutTransition } from '../../layout-transition';
import attributes from './attributes';

const FrontendBlock = ( {
	title,
	description,
	children,
	className,
	showPrice,
	showIcon,
	shippingText,
	localPickupText,
}: {
	title: string;
	description: string;
	children: JSX.Element;
	className?: string;
	showPrice: boolean;
	showIcon: boolean;
	shippingText: string;
	localPickupText: string;
} ) => {
	const { showFormStepNumbers } = useCheckoutBlockContext();
	const { checkoutIsProcessing, prefersCollection } = useSelect(
		( select ) => {
			const checkoutStore = select( checkoutStoreDescriptor );
			return {
				checkoutIsProcessing: checkoutStore.isProcessing(),
				prefersCollection: checkoutStore.prefersCollection(),
			};
		}
	);

	const { setPrefersCollection } = useDispatch( checkoutStoreDescriptor );
	const { selectShippingRate } = useDispatch( cartStore );
	const { needsShipping, isCollectable, shippingRates } = useShippingData();
	// The view transition applies the change a frame or two after the click. Until then, show the shopper's
	// choice on the toggle itself, so it's selected straight away.
	const [ pendingMethod, setPendingMethod ] = useState< string | null >(
		null
	);

	// Note that display logic is also found in plugins/woocommerce/client/blocks/assets/js/blocks/checkout/inner-blocks/register-components.ts
	// where the block is not registered if the conditions are not met.
	if (
		! SHIPPING_ENABLED ||
		! needsShipping ||
		! isCollectable ||
		! LOCAL_PICKUP_ENABLED ||
		! SHIPPING_METHODS_EXIST
	) {
		return null;
	}

	const applyMethod = ( method: string ) => {
		if ( method === 'pickup' ) {
			void setPrefersCollection( true );
		} else {
			void setPrefersCollection( false );

			// When switching to Ship, if no non-pickup shipping rates are
			// available (hidden because no address entered), clear the pickup
			// selection from the session so totals don't show the pickup cost.
			const hasNonPickupRates = shippingRates.some(
				( { shipping_rates: rates } ) =>
					rates.some(
						( rate ) => ! hasCollectableRate( rate.method_id )
					)
			);
			if ( ! hasNonPickupRates ) {
				selectShippingRate( '', null );
			}
		}
	};

	// The address and pickup sections swap, so the sections below slide instead of jumping.
	const onChange = ( method: string ) => {
		if (
			startCheckoutLayoutTransition( () =>
				flushSync( () => {
					setPendingMethod( null );
					applyMethod( method );
				} )
			)
		) {
			setPendingMethod( method );
		} else {
			applyMethod( method );
		}
	};

	return (
		<FormStep
			id="shipping-method"
			disabled={ checkoutIsProcessing }
			className={ clsx(
				'wc-block-checkout__shipping-method',
				className
			) }
			title={ title }
			description={ description }
			showStepNumber={ showFormStepNumbers }
		>
			<Block
				checked={
					pendingMethod ??
					( prefersCollection ? 'pickup' : 'shipping' )
				}
				onChange={ onChange }
				showPrice={ showPrice }
				showIcon={ showIcon }
				localPickupText={ localPickupText }
				shippingText={ shippingText }
			/>
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
