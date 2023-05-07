/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useFormContext,
	__experimentalConditionalWrapper as ConditionalWrapper,
} from '@woocommerce/components';
import { getCheckboxTracks } from '@woocommerce/product-editor';
import { Tooltip, ToggleControl } from '@wordpress/components';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

export const InventoryTrackQuantityField = () => {
	const { getCheckboxControlProps } = useFormContext< Product >();

	const canManageStock = getAdminSetting( 'manageStock', 'yes' ) === 'yes';

	return (
		<ConditionalWrapper
			condition={ ! canManageStock }
			wrapper={ ( children: JSX.Element ) => (
				<Tooltip
					text={ __(
						'Quantity tracking is disabled for all products. Go to global store settings to change it.',
						'woocommerce'
					) }
					position="top center"
				>
					<div className="woocommerce-product-form__tooltip-disabled-overlay">
						{ children }
					</div>
				</Tooltip>
			) }
		>
			<ToggleControl
				label={ __( 'Track quantity for this product', 'woocommerce' ) }
				{ ...getCheckboxControlProps(
					'manage_stock',
					getCheckboxTracks( 'manage_stock' )
				) }
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore This prop does exist, but is not typed in @wordpress/components.
				disabled={ ! canManageStock }
			/>
		</ConditionalWrapper>
	);
};
