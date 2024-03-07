/**
 * Internal dependencies
 */
import { Metadata } from '../../../types';

export type EditModalProps = {
	initialValue: Metadata< string >;
	onUpdate( value: Metadata< string > ): void;
	onCancel(): void;
};
