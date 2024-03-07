/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { useCustomFields } from '../../hooks/use-custom-fields';
import { EmptyState } from './empty-state';
import type { CustomFieldsProps } from './types';

export function CustomFields( { className, ...props }: CustomFieldsProps ) {
	const { customFields } = useCustomFields();

	if ( customFields.length === 0 ) {
		return <EmptyState />;
	}

	return (
		<table
			{ ...props }
			className={ classNames(
				'woocommerce-product-custom-fields__table',
				className
			) }
		>
			<thead>
				<tr className="woocommerce-product-custom-fields__table-row">
					<th>{ __( 'Name', 'woocommerce' ) }</th>
					<th>{ __( 'Value', 'woocommerce' ) }</th>
					<th>{ __( 'Actions', 'woocommerce' ) }</th>
				</tr>
			</thead>
			<tbody>
				{ customFields.map( ( customField ) => (
					<tr
						className="woocommerce-product-custom-fields__table-row"
						key={ customField.id ?? customField.key }
					>
						<td className="woocommerce-product-custom-fields__table-datacell">
							{ customField.key }
						</td>
						<td className="woocommerce-product-custom-fields__table-datacell">
							{ customField.value }
						</td>
						<td className="woocommerce-product-custom-fields__table-datacell"></td>
					</tr>
				) ) }
			</tbody>
		</table>
	);
}
