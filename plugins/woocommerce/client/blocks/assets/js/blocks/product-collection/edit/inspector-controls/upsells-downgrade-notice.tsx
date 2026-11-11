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

		if ( ! site ) {
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
		const hasProductDetailsBlock = !! findBlock( {
			blocks: blockEditor.getBlocks(),
			findCondition: ( block ) =>
				block.name === PRODUCT_DETAILS_BLOCK_NAME,
		} );

		editEntityRecord( 'root', 'site', undefined, {
			[ UPSELLS_MIGRATED_OPTION ]: 'no',
		} );

		if ( hasProductDetailsBlock ) {
			void removeBlock( clientId );
			return;
		}

		const align = blockEditor.getBlockAttributes( clientId )?.align;
		const productDetailsBlock = createBlock(
			PRODUCT_DETAILS_BLOCK_NAME,
			typeof align === 'string' && align !== '' ? { align } : {}
		);

		void replaceBlock( clientId, productDetailsBlock );
	};

	return (
		<UpgradeDowngradeNotice
			isDismissible={ true }
			actionLabel={ buttonLabel }
			onActionClick={ handleClick }
		>
			{ notice }
		</UpgradeDowngradeNotice>
	);
};
