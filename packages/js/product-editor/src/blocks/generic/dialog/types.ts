/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

export interface DialogBlockAttributes extends BlockAttributes {
	title: string;
	isOpen?: boolean;
	onClose?(): void;
}
