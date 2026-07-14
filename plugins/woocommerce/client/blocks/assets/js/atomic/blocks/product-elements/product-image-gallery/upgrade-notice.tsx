/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';

/**
 * Internal dependencies
 */
import { upgradeToBlockifiedProductGallery } from '../../../../blocks/product-gallery/edit-utils';

export const UpgradeNotice = ( {
	blockClientId,
}: {
	blockClientId: string;
} ) => {
	const notice = createInterpolateElement(
		__(
			'Upgrade to the <strongText /> for more flexibility.',
			'woocommerce'
		),
		{
			strongText: (
				<strong>
					{ __( `Product Gallery block`, 'woocommerce' ) }
				</strong>
			),
		}
	);

	const buttonLabel = __( 'Use the Product Gallery block', 'woocommerce' );

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
