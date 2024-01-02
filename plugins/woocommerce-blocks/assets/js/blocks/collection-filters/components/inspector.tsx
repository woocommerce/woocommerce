/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { PanelBody, Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createBlock, BlockInstance } from '@wordpress/blocks';

const Inspector = ( { clientId }: { clientId: string } ) => {
	const { replaceBlock } = useDispatch( 'core/block-editor' );
	const block = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getBlock( clientId );
	} );

	const downgradeBlock = () => {
		if ( ! block ) return;
		const filterBlock = block.innerBlocks[ 0 ];
		const filterType = filterBlock.name.replace(
			'woocommerce/collection-',
			''
		);
		const innerBlocks: BlockInstance[] = [
			createBlock( `woocommerce/${ filterType }`, {
				...filterBlock.attributes,
				heading: '',
			} ),
		];
		const headingBlock = filterBlock.innerBlocks.find(
			( item ) => item.name === 'core/heading'
		);

		if ( headingBlock ) {
			innerBlocks.unshift(
				createBlock( 'core/heading', headingBlock.attributes )
			);
		}

		replaceBlock(
			clientId,
			createBlock(
				`woocommerce/filter-wrapper`,
				{ filterType },
				innerBlocks
			)
		);
	};

	if ( block?.innerBlocks.length !== 1 ) return null;

	return (
		<InspectorControls key="inspector">
			<PanelBody title={ __( 'Legacy Block', 'woocommerce' ) }>
				<p>
					{ __(
						'You can restore to legacy filter blocks incase something went wrong.',
						'woocommerce'
					) }
				</p>
				<Button
					variant="secondary"
					size="small"
					onClick={ downgradeBlock }
				>
					{ __( 'Restore', 'woocommerce' ) }
				</Button>
			</PanelBody>
		</InspectorControls>
	);
};

export default Inspector;
