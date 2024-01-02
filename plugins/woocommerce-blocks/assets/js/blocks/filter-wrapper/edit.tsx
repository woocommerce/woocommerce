/**
 * External dependencies
 */
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from '@wordpress/block-editor';
import { createBlock, type BlockEditProps } from '@wordpress/blocks';
import { PanelBody, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Attributes } from './types';

const Edit = ( { attributes, clientId }: BlockEditProps< Attributes > ) => {
	const block = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getBlock( clientId );
	} );
	const { replaceBlock } = useDispatch( 'core/block-editor' );

	const upgradeBlock = useCallback( () => {
		if ( ! block || ! block.innerBlocks ) return;
		const filterType = block.attributes.filterType;
		const filterBlock = block.innerBlocks.find(
			( item ) => item.name === `woocommerce/${ filterType }`
		);
		if ( ! filterBlock ) return;

		const { lock, ...filterBlockAttributes } = filterBlock.attributes;
		const headingBlock = block.innerBlocks.find(
			( item ) => item.name === 'core/heading'
		);

		replaceBlock(
			clientId,
			createBlock( `woocommerce/collection-filters`, {}, [
				createBlock(
					`woocommerce/collection-${ filterType }`,
					filterBlockAttributes,
					headingBlock
						? [
								createBlock(
									'core/heading',
									headingBlock.attributes
								),
						  ]
						: []
				),
			] )
		);
	}, [ block, clientId, replaceBlock ] );

	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls key="inspector">
				<PanelBody title={ __( 'Block Upgrade', 'woocommerce' ) }>
					<p>
						{ __(
							'We have created new filter blocks with better performance and flexibility.',
							'woocommerce'
						) }
					</p>
					<Button
						variant="primary"
						size="small"
						onClick={ upgradeBlock }
					>
						{ __( 'Upgrade', 'woocommerce' ) }
					</Button>
				</PanelBody>
			</InspectorControls>
			<InnerBlocks
				allowedBlocks={ [ 'core/heading' ] }
				template={ [
					[
						'core/heading',
						{ level: 3, content: attributes.heading || '' },
					],
					[
						`woocommerce/${ attributes.filterType }`,
						{
							heading: '',
							lock: {
								remove: true,
							},
						},
					],
				] }
			/>
		</div>
	);
};

export default Edit;
