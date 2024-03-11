/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import blockEditWithBoundAttribute from './block-edit-with-binding-attrs';
import { isBlockAllowed } from '..';

export default function extendBlockWithBoundAttributes(
	settings: BlockConfiguration,
	name: string
): BlockConfiguration {
	if ( ! isBlockAllowed( name ) ) {
		return settings;
	}

	return {
		...settings,
		/*
		 * Expose relevant data through
		 * the block context.
		 */
		usesContext: [
			...new Set( [
				...( settings.usesContext || [] ),
				'postId',
				'postType',
				'queryId',
			] ),
		],
		edit: settings?.edit
			? blockEditWithBoundAttribute( settings.edit )
			: undefined,
	};
}
