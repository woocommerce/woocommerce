/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Entity } from '../types';
import { SettingsEntityRecord } from './types';

export const SETTINGS_ENTITY: Entity = {
	name: 'settings',
	kind: 'root',
	baseURL: '/wc/v4/settings',
	label: __( 'Settings', 'woocommerce' ),
	getTitle: ( record ) => {
		const recordData = record as SettingsEntityRecord;
		return (
			recordData?.id.charAt( 0 ).toUpperCase() +
			recordData?.id.slice( 1 ) +
			' settings'
		);
	},
	key: 'id',
	supportsPagination: false,
	plural: __( 'Settings', 'woocommerce' ),
};
