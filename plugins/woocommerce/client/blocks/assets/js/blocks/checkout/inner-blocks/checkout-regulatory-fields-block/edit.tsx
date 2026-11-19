/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Notice } from '@wordpress/components';
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

	return (
		<FormStepBlock
			setAttributes={ setAttributes }
			attributes={ attributes }
			className={ clsx(
				'wc-block-checkout__regulatory-fields',
				attributes?.className
			) }
		>
			{ /* The editor previews a sample cart, which no regulatory field applies to, so explain when shoppers see the section. */ }
			{ fields.length === 0 ? (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'Shoppers see this section only when their order needs extra information by law, such as the CPF/CNPJ for orders delivered to Brazil.',
						'woocommerce'
					) }
				</Notice>
			) : (
				<Noninteractive>
					<Block />
				</Noninteractive>
			) }
		</FormStepBlock>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
