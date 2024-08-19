/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { Icon, Notice, Button } from '@wordpress/components';
import {
	store as blockEditorStore,
	InspectorControls,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { select, dispatch } from '@wordpress/data';
import { page } from '@wordpress/icons';
import {
	BLOCK_DESCRIPTION,
	BLOCK_TITLE,
} from '@woocommerce/atomic-blocks/product-elements/summary/constants';
import { createInterpolateElement, type ElementType } from '@wordpress/element';
import { type EditorBlock } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { registerElementVariation } from './utils';

export const CORE_NAME = 'core/post-excerpt';
export const VARIATION_NAME = 'woocommerce/product-query/product-summary';

registerElementVariation( CORE_NAME, {
	blockDescription: BLOCK_DESCRIPTION,
	blockIcon: <Icon icon={ page } />,
	blockTitle: BLOCK_TITLE,
	variationName: VARIATION_NAME,
	scope: [],
} );

function isProductSummaryBlockVariation( block ) {
	return (
		block.name === CORE_NAME &&
		block.attributes.__woocommerceNamespace === VARIATION_NAME
	);
}

const UpgradeNotice = ( { clientId }: { clientId: string } ) => {
	const notice = createInterpolateElement(
		__(
			"There's <strongText /> with important fixes and brand new features.",
			'woocommerce'
		),
		{
			strongText: (
				<strong>
					{ __( `new version of Product Summary`, 'woocommerce' ) }
				</strong>
			),
		}
	);

	const buttonLabel = __( 'Upgrade now (just this block)', 'woocommerce' );

	const handleClick = () => {
		const blocks =
			select( blockEditorStore ).getBlocksByClientId( clientId );

		if ( blocks.length ) {
			const currentBlock = blocks[ 0 ];
			const {
				excerptLength,
				showMoreOnNewLine,
				moreText,
				...restAttributes
			} = currentBlock.attributes;
			const productSummaryBlock = createBlock(
				'woocommerce/product-summary',
				restAttributes
			);
			dispatch( blockEditorStore ).replaceBlock(
				clientId,
				productSummaryBlock
			);
		}
	};

	return (
		<Notice isDismissible={ false }>
			<>{ notice }</>
			<br />
			<br />
			<Button variant="link" onClick={ handleClick }>
				{ buttonLabel }
			</Button>
		</Notice>
	);
};

export const withProductSummaryUpgradeNotice =
	< T extends EditorBlock< T > >( BlockEdit: ElementType ) =>
	( props ) => {
		return isProductSummaryBlockVariation( props ) ? (
			<>
				<InspectorControls>
					<UpgradeNotice clientId={ props.clientId } />
				</InspectorControls>
				<BlockEdit { ...props } />
			</>
		) : (
			<BlockEdit { ...props } />
		);
	};

addFilter(
	'editor.BlockEdit',
	VARIATION_NAME,
	withProductSummaryUpgradeNotice
);
