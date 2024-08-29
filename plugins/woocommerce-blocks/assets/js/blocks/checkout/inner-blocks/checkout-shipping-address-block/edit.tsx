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
import {
	useCheckoutBlockContext,
	useCheckoutBlockControlsContext,
} from '../../context';
import Block from './block';

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
	const { showShippingFields } = useCheckoutAddress();

	if ( ! showShippingFields ) {
		return null;
	}

	// This is needed to force the block to re-render when the requireApartmentField changes.
	const blockKey = `shipping-address-${
		requireApartmentField ? 'visible' : 'hidden'
	}-address-2`;

	return (
		<FormStepBlock
			setAttributes={ setAttributes }
			attributes={ attributes }
			className={ clsx(
				'wc-block-checkout__shipping-fields',
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
			<AdditionalFields block={ innerBlockAreas.SHIPPING_ADDRESS } />
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
