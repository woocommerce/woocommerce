/**
 * External dependencies
 */
import classnames from 'classnames';
import { useRef, useEffect } from '@wordpress/element';
import { withFilteredAttributes } from '@woocommerce/shared-hocs';
import { FormStep } from '@woocommerce/blocks-components';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';
import { useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import Block from './block';
import attributes from './attributes';
import { useCheckoutBlockContext } from '../../context';
import {
	getBillingAddresssBlockTitle,
	getBillingAddresssBlockDescription,
} from './utils';

const FrontendBlock = ( {
	title,
	description,
	showStepNumber,
	children,
	className,
}: {
	title: string;
	description: string;
	showStepNumber: boolean;
	children: JSX.Element;
	className?: string;
} ): JSX.Element | null => {
	const checkoutIsProcessing = useSelect( ( select ) =>
		select( CHECKOUT_STORE_KEY ).isProcessing()
	);
	const {
		requireCompanyField,
		requirePhoneField,
		showApartmentField,
		showCompanyField,
		showPhoneField,
	} = useCheckoutBlockContext();
	const {
		showBillingFields,
		forcedBillingAddress,
		useBillingAsShipping,
		useShippingAsBilling,
	} = useCheckoutAddress();

	// If initial state was true, force editing to true so address fields are visible if the useShippingAsBilling option is unchecked.
	const toggledUseShippingAsBilling = useRef( useShippingAsBilling );

	useEffect( () => {
		if ( useShippingAsBilling ) {
			toggledUseShippingAsBilling.current = true;
		}
	}, [ useShippingAsBilling ] );

	if ( ! showBillingFields && ! useBillingAsShipping ) {
		return null;
	}

	title = getBillingAddresssBlockTitle( title, forcedBillingAddress );
	description = getBillingAddresssBlockDescription(
		description,
		forcedBillingAddress
	);
	return (
		<FormStep
			id="billing-fields"
			disabled={ checkoutIsProcessing }
			className={ classnames(
				'wc-block-checkout__billing-fields',
				className
			) }
			title={ title }
			description={ description }
			showStepNumber={ showStepNumber }
		>
			<Block
				requireCompanyField={ requireCompanyField }
				showApartmentField={ showApartmentField }
				showCompanyField={ showCompanyField }
				showPhoneField={ showPhoneField }
				requirePhoneField={ requirePhoneField }
				forceEditing={ toggledUseShippingAsBilling.current }
			/>
			{ children }
		</FormStep>
	);
};

export default withFilteredAttributes( attributes )( FrontendBlock );
