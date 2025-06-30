/**
 * External dependencies
 */
import clsx from 'clsx';
import { useBlockProps } from '@wordpress/block-editor';
import { useCheckoutAddress } from '@woocommerce/base-context/hooks';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import {
	FormStepBlock,
	AdditionalFields,
	AdditionalFieldsContent,
} from '../../form-step';
import Block from './block';
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
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element | null => {
	const { showShippingFields } = useCheckoutAddress();

	if ( ! showShippingFields ) {
		return null;
	}

	return (
		<>
			<AddressFieldControls />
			<FormStepBlock
				setAttributes={ setAttributes }
				attributes={ attributes }
				className={ clsx(
					'wc-block-checkout__shipping-fields',
					attributes?.className
				) }
			>
				<Block />
				<AdditionalFields block={ innerBlockAreas.SHIPPING_ADDRESS } />
			</FormStepBlock>
		</>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<AdditionalFieldsContent />
		</div>
	);
};
