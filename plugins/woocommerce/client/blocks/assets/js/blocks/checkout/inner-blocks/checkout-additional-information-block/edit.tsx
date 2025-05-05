/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { FormStepBlock } from '@woocommerce/blocks/checkout/form-step';
import clsx from 'clsx';
import { ORDER_FORM_KEYS } from '@woocommerce/block-settings';
import { useCheckoutAddress } from '@woocommerce/base-context';
import { useFormFields } from '@woocommerce/base-components/cart-checkout';

/**
 * Internal dependencies
 */
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
} ) => {
	const { defaultFields } = useCheckoutAddress();
	const formFields = useFormFields( ORDER_FORM_KEYS, defaultFields, 'order' );
	if (
		formFields.length === 0 ||
		formFields.every( ( field ) => !! field.hidden )
	) {
		return null;
	}

	return (
		<FormStepBlock
			setAttributes={ setAttributes }
			attributes={ attributes }
			className={ clsx(
				'wc-block-checkout__additional-information-fields',
				attributes?.className
			) }
		>
			<Block />
		</FormStepBlock>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
