/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { PanelBody, Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createBlock, BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { FilterType } from '../types';

const Downgrade = ( {
	clientId,
	filterType,
}: {
	clientId: string;
	filterType: FilterType;
} ) => {
	const { replaceBlock } = useDispatch( 'core/block-editor' );
	const block = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getBlock( clientId );
	} );

	const downgradeBlock = () => {
		if ( ! block ) return;

		const filterBlock = block.innerBlocks.find( ( item ) =>
			item.name.includes( 'filter' )
		);

		if ( ! filterBlock ) return;

		const headingBlock = block.innerBlocks.find(
			( item ) => item.name === 'core/heading'
		);

		const innerBlocks: BlockInstance[] = [];

		if ( headingBlock ) {
			innerBlocks.push(
				createBlock( 'core/heading', headingBlock.attributes )
			);
		}

		innerBlocks.push(
			createBlock( `woocommerce/${ filterType }`, {
				...filterBlock.attributes,
				heading: '',
			} )
		);

		replaceBlock(
			clientId,
			createBlock(
				`woocommerce/filter-wrapper`,
				{ filterType },
				innerBlocks
			)
		);
	};

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

export default Downgrade;
