/*
 * External dependencies
 */
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { createElement } from '@wordpress/element';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { Button, Flex, FlexItem } from '@wordpress/components';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useWooBlockProps } from '@woocommerce/block-templates';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useInnerBlocksProps } from '@wordpress/block-editor';

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
