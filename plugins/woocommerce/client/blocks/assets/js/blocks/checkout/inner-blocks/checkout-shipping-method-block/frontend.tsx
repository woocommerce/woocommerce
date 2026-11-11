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
import { useLayoutEffect, useRef } from '@wordpress/element';
import { isUsingKeyboard } from '@woocommerce/base-utils/input-modality';
import {
	captureSwap,
	playSwap,
	type SwapSnapshot,
} from '@woocommerce/base-utils/swap-sections';

/**
 * Internal dependencies
 */
import Block from './block';
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

	// Switching swaps the sections below as one movement, which moves the way the selection did (see `playSwap`).
	// Keyboard changes apply at once.
	const swap = useRef< SwapSnapshot | null >( null );
	useLayoutEffect( () => {
		if ( swap.current ) {
			playSwap( swap.current );
			swap.current = null;
		}
	}, [ prefersCollection ] );

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

	const onChange = ( method: string ) => {
		const step = document.getElementById( 'shipping-method' );
		// Ship renders first, then Pickup.
		const [ ship, pickup ] = Array.from(
			step?.querySelectorAll(
				'.wc-block-checkout__shipping-method-option'
			) ?? []
		).map( ( option ) => {
			const rect = option.getBoundingClientRect();
			return rect.left + rect.width / 2;
		} );
		const towardPickup = Math.sign( ( pickup ?? 0 ) - ( ship ?? 0 ) ) || 1;
		swap.current =
			isUsingKeyboard() || ( method === 'pickup' ) === prefersCollection
				? null
				: captureSwap(
						step?.parentElement ?? null,
						method === 'pickup' ? towardPickup : -towardPickup
					);
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
				checked={ prefersCollection ? 'pickup' : 'shipping' }
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
