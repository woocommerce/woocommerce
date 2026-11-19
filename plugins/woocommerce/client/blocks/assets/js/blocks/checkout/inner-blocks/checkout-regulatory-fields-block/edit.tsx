/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { FormStepBlock } from '@woocommerce/blocks/checkout/form-step';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import Block from './block';
import { useRegulatoryFields } from './use-regulatory-fields';

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
	const fields = useRegulatoryFields();

	// The editor previews a sample cart, which no regulatory field applies to.
	if ( fields.length === 0 ) {
		return null;
	}

	return (
		<FormStepBlock
			setAttributes={ setAttributes }
			attributes={ attributes }
			className={ clsx(
				'wc-block-checkout__regulatory-fields',
				attributes?.className
			) }
		>
			<Noninteractive>
				<Block />
			</Noninteractive>
		</FormStepBlock>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
