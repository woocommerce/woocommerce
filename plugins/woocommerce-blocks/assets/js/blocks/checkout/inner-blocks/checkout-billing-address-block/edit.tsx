/**
 * External dependencies
 */
import clsx from 'clsx';
import { useBlockProps } from '@wordpress/block-editor';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import type { BlockAttributes } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import {
	FormStepBlock,
	AdditionalFields,
	AdditionalFieldsContent,
} from '../../form-step';
import {
	useCheckoutBlockContext,
	useCheckoutBlockControlsContext,
} from '../../context';
import Block from './block';
import {
	getBillingAddressBlockTitle,
	getBillingAddressBlockDescription,
} from './utils';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		title: string;
		description: string;
		showStepNumber: boolean;
		className: string;
	};
	setAttributes: ( attributes: BlockAttributes ) => void;
} ): JSX.Element | null => {
	const {
		showCompanyField,
		requireCompanyField,
		showApartmentField,
		requireApartmentField,
		showPhoneField,
		requirePhoneField,
	} = useCheckoutBlockContext();
	const { addressFieldControls: Controls } =
		useCheckoutBlockControlsContext();
	const { showBillingFields, forcedBillingAddress, useBillingAsShipping } =
		useCheckoutAddress();

	if ( ! showBillingFields && ! useBillingAsShipping ) {
		return null;
	}
	attributes.title = getBillingAddressBlockTitle(
		attributes.title,
		forcedBillingAddress
	);
	attributes.description = getBillingAddressBlockDescription(
		attributes.description,
		forcedBillingAddress
	);

	// This is needed to force the block to re-render when the requireApartmentField changes.
	const blockKey = `billing-address-${
		requireApartmentField ? 'visible' : 'hidden'
	}-address-2`;

	return (
		<FormStepBlock
			setAttributes={ setAttributes }
			attributes={ attributes }
			className={ clsx(
				'wc-block-checkout__billing-fields',
				attributes?.className
			) }
		>
			<Controls />
			<Block
				key={ blockKey }
				showCompanyField={ showCompanyField }
				requireCompanyField={ requireCompanyField }
				showApartmentField={ showApartmentField }
				requireApartmentField={ requireApartmentField }
				showPhoneField={ showPhoneField }
				requirePhoneField={ requirePhoneField }
			/>
			<AdditionalFields block={ innerBlockAreas.BILLING_ADDRESS } />
		</FormStepBlock>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<AdditionalFieldsContent />
		</div>
	);
};
