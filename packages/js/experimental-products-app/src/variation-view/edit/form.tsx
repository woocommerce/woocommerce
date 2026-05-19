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
	getVisibleProductEditFields,
} from '../../product-edit/utils';
import { VARIATION_FORM_FIELDS } from './form-fields';

type VariationEditFormProps = {
	editableFields: ReturnType< typeof getProductEditFields >;
	onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	selectedVariations: ProductEntityRecord[];
};

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
		fields: VARIATION_FORM_FIELDS,
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
