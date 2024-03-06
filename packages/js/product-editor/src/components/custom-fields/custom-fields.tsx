/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { useCustomFields } from '../../hooks/use-custom-fields';
import { EmptyState } from './empty-state';
import type { CustomFieldsProps } from './types';

export function CustomFields( {}: CustomFieldsProps ) {
	const { customFields, removeCustomField } = useCustomFields();

	if ( customFields.length === 0 ) {
		return <EmptyState />;
	}

	function removeButtonClickHandler( key: string ) {
		return function handleRemoveButtonClick() {
			removeCustomField( key );
		};
	}

	return (
		<table className="woocommerce-product-custom-fields__table">
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
						<td className="woocommerce-product-custom-fields__table-datacell">
							<Button
								icon={ closeSmall }
								aria-label={ __(
									'Remove custom field',
									'woocommerce'
								) }
								onClick={ removeButtonClickHandler(
									customField.key
								) }
							/>
						</td>
					</tr>
				) ) }
			</tbody>
		</table>
	);
}
