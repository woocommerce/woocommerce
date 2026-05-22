/**
 * External dependencies
 */
import { DataForm } from '@wordpress/dataviews';
import type { FormField } from '@wordpress/dataviews';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import {
	buildMergedProductEditData,
	getProductEditFields,
} from '../../product-edit/utils';
import { VARIATION_FORM_FIELDS } from './form-fields';
import type { VariationEditFieldId } from '../fields/registry';

type VariationFormField = VariationEditFieldId | FormField;

type VariationEditFormProps = {
	editableFields: ReturnType< typeof getProductEditFields >;
	onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	selectedVariations: ProductEntityRecord[];
};

function pruneFormFields(
	fields: VariationFormField[],
	visibleIds: Set< string >
): VariationFormField[] {
	return fields.reduce< VariationFormField[] >( ( acc, field ) => {
		if ( typeof field === 'string' ) {
			if ( visibleIds.has( field ) ) {
				acc.push( field );
			}
			return acc;
		}
		const prunedChildren = pruneFormFields(
			( field.children ?? [] ) as VariationFormField[],
			visibleIds
		);
		if ( prunedChildren.length > 0 ) {
			acc.push( { ...field, children: prunedChildren } );
		}
		return acc;
	}, [] );
}

export function VariationEditForm( {
	editableFields,
	onChange,
	selectedVariations,
}: VariationEditFormProps ) {
	const mergedData = buildMergedProductEditData( selectedVariations );
	const isBulkEdit = selectedVariations.length > 1;
	const adminSettings = getSetting( 'admin', {} );
	const isCostOfGoodsSoldFeatureEnabled =
		// @ts-expect-error - This setting is not typed yet.
		adminSettings?.wcAdminFeatures?.includes( 'cost-of-goods-sold' );
	const visibleFields = editableFields.filter( ( field ) => {
		if (
			field.id === 'cost_of_goods_sold' &&
			! isCostOfGoodsSoldFeatureEnabled
		) {
			return false;
		}
		if ( isBulkEdit && field.id === 'sku' ) {
			return false;
		}
		if ( typeof field.isVisible !== 'function' ) {
			return true;
		}
		return selectedVariations.every( ( v ) => field.isVisible!( v ) );
	} );
	const visibleFieldIds = new Set( visibleFields.map( ( f ) => f.id ) );

	const form = {
		type: 'regular' as const,
		labelPosition: 'top' as const,
		fields: pruneFormFields( VARIATION_FORM_FIELDS, visibleFieldIds ),
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
