/**
 * External dependencies
 */
import { DataForm } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import {
	buildMergedProductEditData,
	getProductEditFields,
	getProductTypeFormFields,
	getVisibleProductEditFields,
} from './utils';

type VariationEditFormProps = {
	editableFields: ReturnType< typeof getProductEditFields >;
	onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	selectedVariations: ProductEntityRecord[];
};

// Dedicated form for editing variations. Mirrors ProductEditForm today but
// lives in its own component so variation-specific UX (parent inheritance
// affordances, custom groupings, etc.) can evolve independently from the
// regular product edit form. Shared field components from the productFields
// registry are still re-used via the standard DataForm pipeline.
export function VariationEditForm( {
	editableFields,
	onChange,
	selectedVariations,
}: VariationEditFormProps ) {
	const mergedData = buildMergedProductEditData( selectedVariations );
	const visibleFields = getVisibleProductEditFields(
		editableFields,
		selectedVariations
	);

	const form = {
		type: 'regular' as const,
		labelPosition: 'top' as const,
		fields: getProductTypeFormFields( selectedVariations, visibleFields ),
	};

	return (
		<div className="woocommerce-variation-edit__form">
			<DataForm
				data={ mergedData }
				fields={ visibleFields }
				form={ form }
				onChange={ onChange }
			/>
		</div>
	);
}
