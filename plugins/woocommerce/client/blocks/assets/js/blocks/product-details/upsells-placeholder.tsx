/**
 * External dependencies
 */
import { Warning, store as blockEditorStore } from '@wordpress/block-editor';
import {
	createBlock,
	createBlocksFromInnerBlocksTemplate,
} from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { select, useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import upsellsCollection from '../product-collection/collections/upsells';

const PRODUCT_COLLECTION_BLOCK_NAME = 'woocommerce/product-collection';
const UPSELLS_MIGRATED_OPTION = 'woocommerce_product_upsells_migrated_to_block';
const HIDE_UPSELLS_MIGRATION_UI_OPTION =
	'woocommerce_hide_product_upsells_migration_ui';

const UpsellsPlaceholder = ( { clientId }: { clientId: string } ) => {
	const { insertBlock } = useDispatch( blockEditorStore );
	const { editEntityRecord } = useDispatch( coreStore );
	const showPlaceholder = useSelect( ( dataSelect ) => {
		const {
			getEntityRecord,
			getEditedEntityRecord,
			hasFinishedResolution,
		} = dataSelect( coreStore );

		getEntityRecord( 'root', 'site' );

		const hasLoaded = hasFinishedResolution( 'getEntityRecord', [
			'root',
			'site',
		] );
		if ( ! hasLoaded ) {
			return false;
		}

		const site = getEditedEntityRecord( 'root', 'site' ) as
			Record< string, unknown > | false | undefined;

		if ( ! site || site[ HIDE_UPSELLS_MIGRATION_UI_OPTION ] === 'yes' ) {
			return false;
		}

		return site[ UPSELLS_MIGRATED_OPTION ] !== 'yes';
	}, [] );

	const migrateToBlock = () => {
		const blockEditor = select( blockEditorStore );
		const rootClientId = blockEditor.getBlockRootClientId( clientId ) ?? '';
		const index = blockEditor.getBlockIndex( clientId, rootClientId );
		const align = blockEditor.getBlockAttributes( clientId )?.align;

		if ( index < 0 ) {
			return;
		}

		editEntityRecord( 'root', 'site', undefined, {
			[ UPSELLS_MIGRATED_OPTION ]: 'yes',
		} );

		const upsellsBlock = createBlock(
			PRODUCT_COLLECTION_BLOCK_NAME,
			{
				...upsellsCollection.attributes,
				query: {
					...upsellsCollection.attributes.query,
					perPage: 5,
				},
				displayLayout: {
					...upsellsCollection.attributes.displayLayout,
					columns: 5,
				},
				...( typeof align === 'string' && align !== ''
					? { align }
					: {} ),
				collection: upsellsCollection.name,
				hideControls: [ 'inherit' ],
			},
			createBlocksFromInnerBlocksTemplate( upsellsCollection.innerBlocks )
		);

		void insertBlock( upsellsBlock, index + 1, rootClientId, true );
	};

	if ( ! showPlaceholder ) {
		return null;
	}

	return (
		<Warning
			actions={ [
				<Button
					key="migrate"
					variant="primary"
					onClick={ migrateToBlock }
				>
					{ __( 'Switch to the Upsells block', 'woocommerce' ) }
				</Button>,
			] }
			className="wc-block-components-actions wc-block-product-details__upsells-placeholder"
		>
			{ __(
				'The Product Details block automatically displays product upsells. If you want more customization options, switch to the Upsells block. You can switch back to the classic upsells display at any time.',
				'woocommerce'
			) }
		</Warning>
	);
};

export default UpsellsPlaceholder;
