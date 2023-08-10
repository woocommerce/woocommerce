/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { VariationsTable } from '../../components/variations-table';
import { VariableProductTour } from './variable-product-tour';

export function Edit( {
	context,
}: {
	context?: {
		isInSelectedTab?: boolean;
	};
} ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<VariationsTable />
			{ context?.isInSelectedTab && <VariableProductTour /> }
		</div>
	);
}
