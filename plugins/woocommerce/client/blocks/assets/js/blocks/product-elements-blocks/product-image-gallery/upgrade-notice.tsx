/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';

/**
 * Internal dependencies
 */
import { upgradeToBlockifiedProductGallery } from './edit-utils';

export const UpgradeNotice = ( {
	blockClientId,
	showAddToCartWithOptionsCompatibilityNotice,
}: {
	blockClientId: string;
	showAddToCartWithOptionsCompatibilityNotice: boolean;
} ) => {
	const notice = showAddToCartWithOptionsCompatibilityNotice
		? __(
				'The classic Product Image Gallery block is not compatible with the Add to Cart + Options block in this template. Switch to the new Product Gallery block for a better experience.',
				'woocommerce'
		  )
		: createInterpolateElement(
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
			status={
				showAddToCartWithOptionsCompatibilityNotice ? 'warning' : 'info'
			}
		>
			{ notice }
		</UpgradeDowngradeNotice>
	);
};
