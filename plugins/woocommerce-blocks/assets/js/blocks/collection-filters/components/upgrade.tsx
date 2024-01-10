/**
 * External dependencies
 */
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { replace } from '@wordpress/icons';
import {
	PanelBody,
	Button,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';

const Upgrade = ( { clientId }: { clientId: string } ) => {
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

	return (
		<>
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
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						onClick={ upgradeBlock }
						icon={ replace }
						label={ __(
							'Upgrade to new filter block',
							'woocommerce'
						) }
					/>
				</ToolbarGroup>
			</BlockControls>
		</>
	);
};

export default Upgrade;
