/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { innerBlockAreas } from '@woocommerce/blocks-checkout';
import type { TemplateArray } from '@wordpress/blocks';
import { useEditorContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import { useForcedLayout, getAllowedBlocks } from '../../../shared';

export const Edit = ( { clientId }: { clientId: string } ): JSX.Element => {
	const blockProps = useBlockProps();
	const allowedBlocks = getAllowedBlocks( innerBlockAreas.EMPTY_MINI_CART );
	const { currentView } = useEditorContext();

	const defaultTemplate = ( [
		[
			'core/heading',
			{
				content: __(
					'Filled mini cart content',
					'woo-gutenberg-products-block'
				),
				level: 2,
			},
		],
	].filter( Boolean ) as unknown ) as TemplateArray;

	useForcedLayout( {
		clientId,
		registeredBlocks: allowedBlocks,
		defaultTemplate,
	} );

	return (
		<div
			{ ...blockProps }
			hidden={
				currentView !== 'woocommerce/filled-mini-cart-contents-block'
			}
		>
			<InnerBlocks
				allowedBlocks={ allowedBlocks }
				templateLock="insert"
			/>
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
