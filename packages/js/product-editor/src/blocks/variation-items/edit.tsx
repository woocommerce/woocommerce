/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { VariationsTable } from '../../components/variations-table';

export function Edit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<VariationsTable />
		</div>
	);
}
