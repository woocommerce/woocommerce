/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';

export const UpgradeNotice = ( props: { upgradeBlock: () => void } ) => {
	const notice = createInterpolateElement(
		__(
			'Upgrade all Products (Beta) blocks on this page to <strongText /> for more features!',
			'woocommerce'
		),
		{
			strongText: (
				<strong>{ __( `Product Collection`, 'woocommerce' ) }</strong>
			),
		}
	);

	const buttonLabel = __( 'Upgrade to Product Collection', 'woocommerce' );

	const handleClick = () => {
		props.upgradeBlock();
		recordEvent(
			'blocks_product_collection_migration_between_products_beta',
			{
				transform_to: 'product_collection',
			}
		);
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
