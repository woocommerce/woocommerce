/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';

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
	useCheckoutBlockContext,
	useCheckoutBlockControlsContext,
} from '../../context';

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
	const { allowCreateAccount } = useCheckoutBlockContext();
	const { accountControls: Controls } = useCheckoutBlockControlsContext();
	return (
		<FormStepBlock
			attributes={ attributes }
			setAttributes={ setAttributes }
		>
			<Controls />
			<Disabled>
				<Block allowCreateAccount={ allowCreateAccount } />
			</Disabled>
			<AdditionalFields area="contactInformation" />
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
