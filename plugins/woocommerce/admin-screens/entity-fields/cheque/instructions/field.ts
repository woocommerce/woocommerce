/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Field } from '@wordpress/dataviews';
import type { ChequeSettings } from '../types';
import { editSetting, getSettingValue } from '../../offline/settings';

export const field: Field< ChequeSettings > = {
	id: 'instructions',
	type: 'text',
	Edit: 'textarea',
	label: __( 'Instructions', 'woocommerce' ),
	description: __(
		'Instructions that will be added to the thank you page and emails.',
		'woocommerce'
	),
	getValue: ( { item } ) => {
		const value = getSettingValue( item.settings.instructions );
		return typeof value === 'string' ? value : '';
	},
	setValue: ( { item, value } ) =>
		editSetting(
			item.settings,
			'instructions',
			typeof value === 'string' ? value : ''
		),
};
