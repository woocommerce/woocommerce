/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/ui';
import type { DataFormControlProps, Field } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import type {
	BulkNumericFieldId,
	BulkNumericOperation,
	ProductBulkEditFormData,
} from './bulk-edit';
import {
	DEFAULT_BULK_NUMERIC_EDIT,
	getBulkNumericOperationFieldId,
	getBulkNumericOperations,
} from './bulk-edit';

const OPERATION_LABELS: Record< BulkNumericOperation, string > = {
	dont_change: __( 'Don’t change', 'woocommerce' ),
	set: __( 'Set to', 'woocommerce' ),
	increase: __( 'Increase by amount', 'woocommerce' ),
	decrease: __( 'Decrease by amount', 'woocommerce' ),
	increase_percent: __( 'Increase by %', 'woocommerce' ),
	decrease_percent: __( 'Decrease by %', 'woocommerce' ),
};

function BulkNumericOperationControl( {
	data,
	field,
	onChange,
}: DataFormControlProps< ProductEntityRecord > ) {
	const items = field.elements ?? [];
	const value =
		( data as ProductBulkEditFormData )[ field.id ] ??
		DEFAULT_BULK_NUMERIC_EDIT.operation;
	const selectedOption =
		items.find( ( option ) => option.value === value ) ?? items[ 0 ];

	return (
		<SelectControl
			label={ field.label }
			hideLabelFromVision
			value={ selectedOption }
			items={ items }
			onValueChange={ ( option ) => {
				onChange( {
					[ field.id ]:
						option?.value ?? DEFAULT_BULK_NUMERIC_EDIT.operation,
				} as Partial< ProductEntityRecord > );
			} }
		/>
	);
}

export function createBulkNumericOperationField(
	field: Field< ProductEntityRecord >,
	fieldId: BulkNumericFieldId
): Field< ProductEntityRecord > {
	return {
		id: getBulkNumericOperationFieldId( fieldId ),
		label: field.label,
		type: 'text',
		enableHiding: false,
		enableSorting: false,
		filterBy: false,
		elements: getBulkNumericOperations( fieldId ).map( ( operation ) => ( {
			label: OPERATION_LABELS[ operation ],
			value: operation,
		} ) ),
		Edit: BulkNumericOperationControl,
	};
}
