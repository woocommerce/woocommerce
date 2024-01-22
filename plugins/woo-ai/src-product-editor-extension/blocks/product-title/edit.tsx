/*
 * External dependencies
 */
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useInnerBlocksProps, useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './editor.scss';

export default function AiTitleBlockEdit() {
	const innerBlockProps = useInnerBlocksProps(
		{},
		{
			template: [
				[
					'woocommerce/product-name-field',
					{ placeholder: 'Book Title' },
				],
			],
			templateLock: 'all',
			allowedBlocks: [ 'woocommerce/product-name-field' ],
		}
	);

	return (
		<div { ...useBlockProps() }>
			<div { ...innerBlockProps } />
			{ __( 'Product Title with AI', 'woocommerce' ) }
		</div>
	);
}
