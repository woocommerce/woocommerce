/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Metadata } from '../../../types';

export type EditModalProps = Omit<
	Modal.Props,
	'title' | 'onRequestClose' | 'children'
> & {
	initialValue: Metadata< string >;
	onUpdate( value: Metadata< string > ): void;
	onCancel(): void;
};
