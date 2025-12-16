/**
 * External dependencies
 */
import type { BlockEditProps as WPBlockEditProps } from '@wordpress/blocks';

/**
 * Block edit props.
 */
export type BlockEditProps = WPBlockEditProps< Record< string, unknown > >;

/**
 * Block save props.
 */
export type BlockSaveProps = {
	attributes: Record< string, unknown >;
};
