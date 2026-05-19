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

type ProductEditFormProps = {
	editableFields: ReturnType< typeof getProductEditFields >;
	onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	selectedProducts: ProductEntityRecord[];
};

export function ProductEditForm( {
	editableFields,
	onChange,
	selectedProducts,
}: ProductEditFormProps ) {
	const mergedData = buildMergedProductEditData( selectedProducts );
	const visibleFields = getVisibleProductEditFields(
		editableFields,
		selectedProducts
	);

	const form = {
		type: 'regular' as const,
		labelPosition: 'top' as const,
		fields: getProductTypeFormFields( selectedProducts, visibleFields ),
	};

	return (
		<div className="woocommerce-product-edit__form">
			<DataForm
				data={ mergedData }
				fields={ visibleFields }
				form={ form }
				onChange={ onChange }
			/>
		</div>
	);
}
