/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useCustomFields } from '../../hooks/use-custom-fields';
import { EmptyState } from './empty-state';
import type { CustomFieldsProps } from './types';

export function CustomFields( {}: CustomFieldsProps ) {
	const [ customFields ] = useCustomFields();

	console.log( customFields );

	return <EmptyState />;
}
