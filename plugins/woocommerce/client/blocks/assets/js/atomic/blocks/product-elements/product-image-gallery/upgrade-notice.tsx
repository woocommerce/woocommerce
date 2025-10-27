/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { select } from '@wordpress/data';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';
import { findBlock } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { replaceBlockWithProductGallery } from '../../../../blocks/product-gallery/edit-utils';

const upgradeToBlockifiedProductGallery = ( blockClientId: string ) => {
	const blocks = select( 'core/block-editor' ).getBlocks();
	const foundBlock = findBlock( {
		blocks,
		findCondition: ( block ) =>
			block.name === metadata.name && block.clientId === blockClientId,
	} );

	if ( foundBlock ) {
		return replaceBlockWithProductGallery( foundBlock.clientId );
	}
	return false;
};

export const UpgradeNotice = ( {
	blockClientId,
}: {
	blockClientId: string;
} ) => {
	const notice = createInterpolateElement(
		__(
			'Gain access to more customization options when you upgrade to the <strongText />.',
			'woocommerce'
		),
		{
			strongText: (
				<strong>
					{ __( `blockified experience`, 'woocommerce' ) }
				</strong>
			),
		}
	);

	const buttonLabel = __(
		'Upgrade to the new Product Gallery block',
		'woocommerce'
	);

	return (
		<UpgradeDowngradeNotice
			isDismissible={ false }
			actionLabel={ buttonLabel }
			onActionClick={ () =>
				upgradeToBlockifiedProductGallery( blockClientId )
			}
		>
			{ notice }
		</UpgradeDowngradeNotice>
	);
};
