/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { VariationsTable } from '../../components/variations-table';
import { VariationOptionsBlockAttributes } from './types';
import { VariableProductTour } from './variable-product-tour';

export function Edit( {
	context,
}: BlockEditProps< VariationOptionsBlockAttributes > & {
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
