/**
 * External dependencies
 */
import {
	registerBlockVariation,
} from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { QUERY_LOOP_ID } from '../constants';
import { RelatedProductsControlsBlockVariationSettings } from './related-products-settings';


registerBlockVariation(
	QUERY_LOOP_ID,
	// @ts-expect-error: `settings` currently does not have a correct type definition in WordPress core.
	RelatedProductsControlsBlockVariationSettings
);
