/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { __experimentalGetSpacingClassesAndStyles } from '@wordpress/block-editor';

export const hasSpacingStyleSupport = () =>
	typeof __experimentalGetSpacingClassesAndStyles === 'function';
