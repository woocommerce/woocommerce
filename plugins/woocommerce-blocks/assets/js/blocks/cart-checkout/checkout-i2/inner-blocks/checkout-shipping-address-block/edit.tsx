/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

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
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const {
		showCompanyField,
		showApartmentField,
		requireCompanyField,
		showPhoneField,
		requirePhoneField,
	} = useCheckoutBlockContext();
	const {
		addressFieldControls: Controls,
	} = useCheckoutBlockControlsContext();
	return (
		<FormStepBlock
			setAttributes={ setAttributes }
			attributes={ attributes }
			className="wc-block-checkout__shipping-fields"
		>
			<Controls />
			<Block
				showCompanyField={ showCompanyField }
				showApartmentField={ showApartmentField }
				requireCompanyField={ requireCompanyField }
				showPhoneField={ showPhoneField }
				requirePhoneField={ requirePhoneField }
			/>
			<AdditionalFields area="shippingAddress" />
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
