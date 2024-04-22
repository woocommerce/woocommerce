/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { FormStepBlock } from '@woocommerce/blocks/checkout/form-step';
import classnames from 'classnames';
import { ORDER_FORM_KEYS } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';

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
	if ( ORDER_FORM_KEYS.length === 0 ) {
		return null;
	}

	return (
		<FormStepBlock
			setAttributes={ setAttributes }
			attributes={ attributes }
			className={ classnames(
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
