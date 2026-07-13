/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';

/**
 * Internal dependencies
 */
import { replaceBlockWithProductGallery } from '../../../../blocks/product-gallery/edit-utils';

export const AddToCartWithOptionsCompatibilityNotice = ( {
	blockClientId,
}: {
	blockClientId: string;
} ) => {
	const notice = __(
		'The classic Product Image Gallery block is not compatible with the Add to Cart + Options block in this template. Switch to the new Product Gallery block for a better experience.',
		'woocommerce'
	);

	const buttonLabel = __(
		'Upgrade to the Product Gallery block',
		'woocommerce'
	);

	return (
		<UpgradeDowngradeNotice
			isDismissible={ false }
			actionLabel={ buttonLabel }
			onActionClick={ () => {
				replaceBlockWithProductGallery( blockClientId );
			} }
			status="warning"
		>
			{ notice }
		</UpgradeDowngradeNotice>
	);
};
