/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { TemplateArray } from '@wordpress/blocks';
import { useStoreCart } from '@woocommerce/base-context';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: { className: string };
	setAttributes: ( any ) => void;
} ): JSX.Element => {
	const { className } = attributes;
	const blockProps = useBlockProps();
	const defaultTemplate = [
		[
			'woocommerce/single-cart-line-item-block',
			{
				className: 'wc-block-cart-line-item',
			},
		],
	] as TemplateArray;

	const { cartItems } = useStoreCart();
	setAttributes( { lineItem: cartItems[ 0 ] } );

	return (
		<div { ...blockProps }>
			<InnerBlocks template={ defaultTemplate } />
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
