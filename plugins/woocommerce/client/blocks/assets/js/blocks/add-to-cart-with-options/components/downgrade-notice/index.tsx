/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { createBlock } from '@wordpress/blocks';
import { dispatch, select } from '@wordpress/data';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';
import { findBlock } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import metadata from '../../block.json';

const downgradeToClassicAddToCartWithOptions = ( blockClientId: string ) => {
	const blocks = select( 'core/block-editor' ).getBlocks();
	const foundBlock = findBlock( {
		blocks,
		findCondition: ( block ) =>
			block.name === metadata.name && block.clientId === blockClientId,
	} );

	if ( ! foundBlock ) {
		return false;
	}

	const newBlock = createBlock( 'woocommerce/add-to-cart-form', {
		quantitySelectorStyle: 'input',
	} );

	dispatch( 'core/block-editor' ).replaceBlock(
		foundBlock.clientId,
		newBlock
	);

	return true;
};

export const DowngradeNotice = ( {
	blockClientId,
}: {
	blockClientId: string;
} ) => {
	const notice = __(
		'Switch back to the classic Add to Cart + Options block.',
		'woocommerce'
	);

	const buttonLabel = __( 'Switch back', 'woocommerce' );

	const handleClick = async () => {
		const downgraded = await downgradeToClassicAddToCartWithOptions(
			blockClientId
		);
		if ( downgraded ) {
			recordEvent( 'blocks_add_to_cart_with_options_migration', {
				transform_to: 'legacy',
			} );
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
