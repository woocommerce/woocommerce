/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { Metadata } from '../../../types';

export type CreateModalProps = Omit<
	Modal.Props,
	'title' | 'onRequestClose' | 'children'
> & {
	onCreate( value: Metadata< string >[] ): void;
	onCancel(): void;
};
