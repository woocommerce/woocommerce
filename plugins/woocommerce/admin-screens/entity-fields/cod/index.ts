/**
 * Fields of the cash on delivery settings page, in the order the form shows them.
 */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Field } from '@wordpress/dataviews';
import type { OfflineGatewaySettings } from '../offline/types';
import { field as enabled } from './enabled/field';
import { field as title } from './title/field';
import { field as description } from './description/field';
import { field as instructions } from './instructions/field';
import { getField as getEnableForMethods } from '../offline/enable-for-methods/field';
import { getField as getEnableForVirtual } from '../offline/enable-for-virtual/field';

/**
 * Get the cash on delivery fields for the entity of the given kind.
 */
export default function getFields(
	kind: string
): Field< OfflineGatewaySettings >[] {
	return [
		enabled,
		title,
		description,
		instructions,
		getEnableForMethods( kind, 'cod' ),
		getEnableForVirtual( __( 'cash on delivery', 'woocommerce' ) ),
	];
}
