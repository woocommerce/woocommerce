/**
 * External dependencies
 */
import clsx from 'clsx';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { FormStep } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { checkoutStore as checkoutStoreDescriptor } from '@woocommerce/block-data';
import { useShippingData } from '@woocommerce/base-context/hooks';
import {
	LOCAL_PICKUP_ENABLED,
	SHIPPING_METHODS_EXIST,
	SHIPPING_ENABLED,
} from '@woocommerce/block-settings';
import { useCheckoutBlockContext } from '@woocommerce/blocks/checkout/context';

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
	const { needsShipping, isCollectable } = useShippingData();

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
		if ( method === 'pickup' ) {
			setPrefersCollection( true );
		} else {
			setPrefersCollection( false );
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
