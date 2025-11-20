/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from './constants';

export default async () => {
	const { onLoad } = dispatch( STORE_NAME );

	await onLoad();
};
