/*
 * External dependencies
 */
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { __ } from '@wordpress/i18n';
import { Button, Flex, FlexItem, ToolbarButton } from '@wordpress/components';
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

export default function AiTitleBlockEdit( { isSelected }) {
	const innerBlockProps = useInnerBlocksProps(
		{},
		{
			templateLock: 'contentOnly',
			allowedBlocks: [ 'woocommerce/product-name-field' ],
		}
	);

	const blockProps = useWooBlockProps( {} );

	return (
		<div { ...blockProps }>
			<Flex justify="flex-end" className="wc-block-ai-title__toolbar">
				<FlexItem>
					<Button
						icon={ ai }
						tabIndex={ 0 }
						variant="secondary"
						label={ __(
							'Get AI suggestions for product title',
							'woocommerce'
						) }
						onClick={ console.log }
					/>
				</FlexItem>
			</Flex>

			<div { ...innerBlockProps } />
		</div>
	);
}
