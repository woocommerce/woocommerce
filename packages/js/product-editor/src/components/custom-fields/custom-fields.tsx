/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement, Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { useCustomFields } from '../../hooks/use-custom-fields';
import { EditModal } from './edit-modal';
import { EmptyState } from './empty-state';
import type { Metadata } from '../../types';
import type { CustomFieldsProps } from './types';

export function CustomFields( {
	className,
	renderActionButtonsWrapper = ( buttons ) => buttons,
	...props
}: CustomFieldsProps ) {
	const { customFields, updateCustomField } = useCustomFields();
	const [ selectedCustomField, setSelectedCustomField ] =
		useState< Metadata< string > >();

	if ( customFields.length === 0 ) {
		return <EmptyState />;
	}

	function customFieldEditButtonClickHandler(
		customField: Metadata< string >
	) {
		return function handleCustomFieldEditButtonClick() {
			setSelectedCustomField( customField );
		};
	}

	function handleEditModalUpdate( customField: Metadata< string > ) {
		updateCustomField( customField );
		setSelectedCustomField( undefined );
	}

	function handleEditModalCancel() {
		setSelectedCustomField( undefined );
	}

	return (
		<>
			{ renderActionButtonsWrapper(
				<Button variant="secondary">
					{ __( 'Add new', 'woocommerce' ) }
				</Button>
			) }

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
							<td className="woocommerce-product-custom-fields__table-datacell">
								<Button
									variant="tertiary"
									onClick={ customFieldEditButtonClickHandler(
										customField
									) }
								>
									{ __( 'Edit', 'woocommerce' ) }
								</Button>
							</td>
						</tr>
					) ) }
				</tbody>
			</table>

			{ selectedCustomField && (
				<EditModal
					initialValue={ selectedCustomField }
					onUpdate={ handleEditModalUpdate }
					onCancel={ handleEditModalCancel }
				/>
			) }
		</>
	);
}
