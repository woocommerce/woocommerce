/*
 * External dependencies
 */
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { __ } from '@wordpress/i18n';
import { ToolbarButton } from '@wordpress/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
	BlockControls,
	useInnerBlocksProps,
	useBlockProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './editor.scss';
import ai from '../../icons/ai';

export default function AiTitleBlockEdit() {
	const innerBlockProps = useInnerBlocksProps(
		{},
		{
			templateLock: 'contentOnly',
			allowedBlocks: [ 'woocommerce/product-name-field' ],
		}
	);

	const blockProps = useWooBlockProps(
		{},
		{
			tabIndex: 0,
		}
	);

	return (
		<div { ...blockProps }>
			<BlockControls>
				<ToolbarButton
					label={ __(
						'Get AI suggestions for product title',
						'woocommerce'
					) }
					icon={ ai }
				>
					{ __( 'Get AI suggestions', 'woocommerce' ) }
				</ToolbarButton>
			</BlockControls>

			<div { ...innerBlockProps } />
		</div>
	);
}
