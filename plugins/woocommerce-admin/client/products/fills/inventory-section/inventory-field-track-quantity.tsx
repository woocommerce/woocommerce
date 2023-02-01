/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useFormContext,
	useFormContext2,
	__experimentalConditionalWrapper as ConditionalWrapper,
} from '@woocommerce/components';
import { Tooltip, ToggleControl } from '@wordpress/components';
import { Product } from '@woocommerce/data';
import { useController } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';
import { getCheckboxTracks } from '../../sections/utils';

export const InventoryTrackQuantityField = () => {
	const { control } = useFormContext2< Product >();
	const { field } = useController( {
		name: 'manage_stock',
		control,
		defaultValue: false,
	} );

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
				checked={ field.value }
				onChange={ ( value ) => {
					field.onChange( value );
					getCheckboxTracks( 'manage_stock' ).onChange( value );
				} }
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore This prop does exist, but is not typed in @wordpress/components.
				disabled={ ! canManageStock }
			/>
		</ConditionalWrapper>
	);
};
