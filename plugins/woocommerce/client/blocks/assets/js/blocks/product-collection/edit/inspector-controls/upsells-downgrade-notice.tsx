/**
 * External dependencies
 */
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';
import { findBlock } from '@woocommerce/utils';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { store as coreStore } from '@wordpress/core-data';
import { select, useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const PRODUCT_DETAILS_BLOCK_NAME = 'woocommerce/product-details';
const UPSELLS_MIGRATED_OPTION = 'woocommerce_product_upsells_migrated_to_block';
const HIDE_UPSELLS_MIGRATION_UI_OPTION =
	'woocommerce_hide_product_upsells_migration_ui';

export const UpsellsDowngradeNotice = ( {
	clientId,
}: {
	clientId: string;
} ) => {
	const { editEntityRecord } = useDispatch( coreStore );
	const { removeBlock, replaceBlock } = useDispatch( blockEditorStore );
	const showNotice = useSelect( ( dataSelect ) => {
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

		return site[ UPSELLS_MIGRATED_OPTION ] === 'yes';
	}, [] );

	if ( ! showNotice ) {
		return null;
	}

	const notice = __(
		'Facing compatibility issues with extensions? You can switch back to the classic upsells display.',
		'woocommerce'
	);

	const buttonLabel = __( 'Switch back', 'woocommerce' );

	const handleClick = () => {
		const blockEditor = select( blockEditorStore );

		// replaceBlock does not check removal locks, so guard both paths here.
		if ( ! blockEditor.canRemoveBlock( clientId ) ) {
			return;
		}

		const hasProductDetailsBlock = !! findBlock( {
			blocks: blockEditor.getBlocks(),
			findCondition: ( block ) =>
				block.name === PRODUCT_DETAILS_BLOCK_NAME,
		} );

		if ( hasProductDetailsBlock ) {
			void removeBlock( clientId );
		} else {
			const align = blockEditor.getBlockAttributes( clientId )?.align;
			const productDetailsBlock = createBlock(
				PRODUCT_DETAILS_BLOCK_NAME,
				typeof align === 'string' && align !== '' ? { align } : {}
			);

			void replaceBlock( clientId, productDetailsBlock );
		}

		// removeBlock can leave the block in place, for example when a removal
		// prompt stops it. Record the downgrade only after this block is gone.
		if ( blockEditor.getBlock( clientId ) ) {
			return;
		}

		editEntityRecord( 'root', 'site', undefined, {
			[ UPSELLS_MIGRATED_OPTION ]: 'no',
		} );
	};

	return (
		<UpgradeDowngradeNotice
			actionLabel={ buttonLabel }
			onActionClick={ handleClick }
		>
			{ notice }
		</UpgradeDowngradeNotice>
	);
};
