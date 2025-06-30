/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';
import { useDispatch, select } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

export const UpgradeNotice = ( { clientId }: { clientId: string } ) => {
	const { replaceBlock, removeBlock, updateBlockAttributes, selectBlock } =
		useDispatch( 'core/block-editor' );

	const notice = createInterpolateElement(
		__(
			'Upgrade all Filter blocks on this page for better performance and more customizability',
			'woocommerce'
		),
		{
			strongText: (
				<strong>{ __( `Product Filters`, 'woocommerce' ) }</strong>
			),
		}
	);

	const buttonLabel = __( 'Upgrade all Filter blocks', 'woocommerce' );

	const handleClick = () => {
		const { getBlocksByName, getBlockParentsByBlockName } =
			select( 'core/block-editor' );

		const blockParent = getBlockParentsByBlockName(
			clientId,
			'woocommerce/filter-wrapper'
		);

		const newBlock = createBlock( 'woocommerce/product-filters' );

		if ( blockParent.length ) {
			replaceBlock( blockParent[ 0 ], newBlock );
		} else {
			replaceBlock( clientId, newBlock );
		}

		const legacyFilterBlockWrapper = getBlocksByName(
			'woocommerce/filter-wrapper'
		);

		// We want to remove all the legacy filter blocks on the page.
		legacyFilterBlockWrapper.forEach( ( blockId: string ) => {
			// We need to disable locked blocks first.
			updateBlockAttributes( blockId, {
				lock: {
					remove: false,
				},
			} );

			removeBlock( blockId );
		} );

		// These are the v1 legacy filters without the wrapper block.
		const v1LegacyFilterBlocks = [
			'woocommerce/active-filters',
			'woocommerce/price-filter',
			'woocommerce/attribute-filter',
			'woocommerce/stock-filter',
		];

		v1LegacyFilterBlocks.forEach( ( blockName ) => {
			const block = getBlocksByName( blockName );

			if ( block.length ) {
				// We need to disable locked blocks first.
				updateBlockAttributes( block[ 0 ], {
					lock: {
						remove: false,
					},
				} );

				removeBlock( block[ 0 ] );
			}
		} );

		// Make sure to put the focus on the newly added Product Filters block.
		selectBlock( newBlock.clientId );
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
