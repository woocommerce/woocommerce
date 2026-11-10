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
import { resolveSelect, select, useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import upsellsCollection from '../product-collection/collections/upsells';

const PRODUCT_COLLECTION_BLOCK_NAME = 'woocommerce/product-collection';
const UPSELLS_MIGRATED_OPTION = 'woocommerce_product_upsells_migrated_to_block';

const UpsellsPlaceholder = ( { clientId }: { clientId: string } ) => {
	const [ isMigrating, setIsMigrating ] = useState( false );
	const { insertBlock } = useDispatch( blockEditorStore );
	const { editEntityRecord } = useDispatch( coreStore );
	const showPlaceholder = useSelect( ( dataSelect ) => {
		const {
			getEntityRecord,
			getEditedEntityRecord,
			hasFinishedResolution,
		} = dataSelect( coreStore );

		// Fetch the site entity. Until it resolves, the option is unknown.
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

		if ( ! site ) {
			return false;
		}

		return site[ UPSELLS_MIGRATED_OPTION ] !== 'yes';
	}, [] );

	const migrateToBlock = async () => {
		const blockEditor = select( blockEditorStore );
		const rootClientId = blockEditor.getBlockRootClientId( clientId ) ?? '';
		const index = blockEditor.getBlockIndex( clientId, rootClientId );
		const align = blockEditor.getBlockAttributes( clientId )?.align;

		if ( index < 0 ) {
			return;
		}

		setIsMigrating( true );
		try {
			const site = await resolveSelect( coreStore ).getEntityRecord(
				'root',
				'site'
			);
			if ( ! site ) {
				return;
			}

			// Stage the option on the site entity so it persists with the editor Save button.
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
				},
				createBlocksFromInnerBlocksTemplate(
					upsellsCollection.innerBlocks
				)
			);

			void insertBlock( upsellsBlock, index + 1, rootClientId, true );
		} finally {
			setIsMigrating( false );
		}
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
					isBusy={ isMigrating }
					disabled={ isMigrating }
					onClick={ () => {
						void migrateToBlock();
					} }
				>
					{ __( 'Switch to the Upsells block', 'woocommerce' ) }
				</Button>,
			] }
			className="wc-block-components-actions wc-block-product-details__upsells-placeholder"
		>
			{ __(
				'The Product Details block automatically displays product upsells. If you want more customization options, switch to the Upsells block.',
				'woocommerce'
			) }
		</Warning>
	);
};

export default UpsellsPlaceholder;
