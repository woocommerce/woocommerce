/**
 * Fields of the direct bank transfer settings page, in the order the form shows them.
 */

/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { Field } from '@wordpress/dataviews';
import type { BacsSettings } from './types';
import { field as enabled } from './enabled/field';
import { field as title } from './title/field';
import { field as description } from './description/field';
import { field as instructions } from './instructions/field';
import { field as accounts } from './accounts/field';
import { getField as getEnableForMethods } from '../offline/enable-for-methods/field';
import { getField as getEnableForVirtual } from '../offline/enable-for-virtual/field';

/**
 * Get the direct bank transfer fields for the entity of the given kind.
 */
export default function getFields( kind: string ): Field< BacsSettings >[] {
	return [
		enabled,
		title,
		description,
		instructions,
		getEnableForMethods( kind, 'bacs' ),
		getEnableForVirtual( __( 'direct bank transfer', 'woocommerce' ) ),
		accounts,
	];
}
