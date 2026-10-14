/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { InspectorControls } from '@wordpress/block-editor';
import {
	createBlock,
	type BlockEditProps,
	type BlockInstance,
} from '@wordpress/blocks';
import { useDispatch, useRegistry } from '@wordpress/data';
import {
	createInterpolateElement,
	type ComponentType,
} from '@wordpress/element';
import { type EditorBlock } from '@woocommerce/types';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';

/**
 * Internal dependencies
 */
import { PRODUCT_SUMMARY_NAMESPACES } from '../variations/constants';

const CORE_NAME = 'core/post-excerpt';

const isProductSummaryBlockVariation = ( props: BlockInstance ) => {
	return (
		props.name === CORE_NAME &&
		PRODUCT_SUMMARY_NAMESPACES.includes(
			props.attributes.__woocommerceNamespace
		)
	);
};

const UpgradeNotice = ( { clientId }: { clientId: string } ) => {
	const registry = useRegistry();
	const { replaceBlock } = useDispatch( 'core/block-editor' );

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
		const { getBlocksByClientId } = registry.select( 'core/block-editor' );
		const blocks = getBlocksByClientId( clientId );

		if ( blocks?.length && blocks[ 0 ] ) {
			const currentBlock = blocks[ 0 ];
			const {
				excerptLength,
				showMoreOnNewLine,
				moreText,
				// Pass the styles to new block
				...restAttributes
			} = currentBlock.attributes;
			const productSummaryBlock = createBlock(
				'woocommerce/product-summary',
				restAttributes
			);
			void replaceBlock( clientId, productSummaryBlock );
		}
	};

	return (
		<UpgradeDowngradeNotice
			isDismissible={ false }
			actionLabel={ buttonLabel }
			onActionClick={ handleClick }
		>
			{ notice }
		</UpgradeDowngradeNotice>
	);
};

const withProductSummaryUpgradeNotice =
	< T extends EditorBlock< T > >( BlockEdit: ComponentType ) =>
	( props: BlockEditProps< T > ) => {
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
	'woocommerce-blocks/product-summary-upgrade-notice',
	withProductSummaryUpgradeNotice
);
