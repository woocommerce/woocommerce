/**
 * External dependencies
 */
import '@woocommerce/internal-ts-config/types/@wordpress/data';
import '@woocommerce/internal-ts-config/types/@wordpress/block-editor';
import '@woocommerce/internal-ts-config/types/@wordpress/editor';
import '@woocommerce/internal-ts-config/types/@wordpress/notices';

/**
 * Internal dependencies
 */
import './store-types';
import './wordpress-modules';

/* eslint-disable @typescript-eslint/no-explicit-any -- some general types in this file need to use "any"  */
/* eslint-disable @typescript-eslint/naming-convention -- we have no control over 3rd-party naming conventions */
/* eslint-disable no-underscore-dangle -- we have no control over 3rd-party naming conventions */

export type FontFamily = {
	name: string;
	slug: string;
	fontFamily: string;
};
