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
import Block from './block';
import {
	getBillingAddresssBlockTitle,
	getBillingAddresssBlockDescription,
} from './utils';
import { AddressFieldControls } from '../../address-field-controls';

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
	const { showBillingFields, forcedBillingAddress, useBillingAsShipping } =
		useCheckoutAddress();

	if ( ! showBillingFields && ! useBillingAsShipping ) {
		return null;
	}
	attributes.title = getBillingAddresssBlockTitle(
		attributes.title,
		forcedBillingAddress
	);
	attributes.description = getBillingAddresssBlockDescription(
		attributes.description,
		forcedBillingAddress
	);

	return (
		<FormStepBlock
			setAttributes={ setAttributes }
			attributes={ attributes }
			className={ clsx(
				'wc-block-checkout__billing-fields',
				attributes?.className
			) }
		>
			<AddressFieldControls />
			<Block />
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
