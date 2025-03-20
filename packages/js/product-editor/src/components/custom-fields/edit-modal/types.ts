/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Metadata } from '../../../types';

export type EditModalProps = Omit<
	React.ComponentProps< typeof Modal >,
	'title' | 'onRequestClose' | 'children'
> & {
	initialValue: Metadata< string >;
	values: Metadata< string >[];
	onUpdate( value: Metadata< string > ): void;
	onCancel(): void;
};
