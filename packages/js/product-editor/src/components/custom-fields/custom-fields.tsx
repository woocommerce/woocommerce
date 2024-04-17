/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement, Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import { recordEvent } from '@woocommerce/tracks';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../constants';
import { useCustomFields } from '../../hooks/use-custom-fields';
import { CreateModal } from './create-modal';
import { EditModal } from './edit-modal';
import { EmptyState } from './empty-state';
import type { Metadata } from '../../types';
import type { CustomFieldsProps } from './types';

export function CustomFields( {
	className,
	renderActionButtonsWrapper = ( buttons ) => buttons,
	...props
}: CustomFieldsProps ) {
	const {
		customFields,
		addCustomFields,
		updateCustomField,
		removeCustomField,
	} = useCustomFields();

	const [ showCreateModal, setShowCreateModal ] = useState( false );
	const [ selectedCustomField, setSelectedCustomField ] =
		useState< Metadata< string > >();

	function handleAddNewButtonClick() {
		setShowCreateModal( true );

		recordEvent( 'product_custom_fields_show_add_modal', {
			source: TRACKS_SOURCE,
		} );
	}

	function customFieldEditButtonClickHandler(
		customField: Metadata< string >
	) {
		return function handleCustomFieldEditButtonClick() {
			setSelectedCustomField( customField );

			recordEvent( 'product_custom_fields_show_edit_modal', {
				source: TRACKS_SOURCE,
				custom_field_id: customField.id,
				custom_field_name: customField.key,
			} );
		};
	}

	function customFieldRemoveButtonClickHandler(
		customField: Metadata< string >
	) {
		return function handleCustomFieldRemoveButtonClick() {
			removeCustomField( customField );

			recordEvent( 'product_custom_fields_remove_button_click', {
				source: TRACKS_SOURCE,
				custom_field_id: customField.id,
				custom_field_name: customField.key,
			} );
		};
	}

	function handleCreateModalCreate( value: Metadata< string >[] ) {
		addCustomFields( value );
		setShowCreateModal( false );
	}

	function handleCreateModalCancel() {
		setShowCreateModal( false );

		recordEvent( 'product_custom_fields_cancel_add_modal', {
			source: TRACKS_SOURCE,
		} );
	}

	function handleEditModalUpdate( customField: Metadata< string > ) {
		updateCustomField( customField );
		setSelectedCustomField( undefined );
	}

	function handleEditModalCancel() {
		setSelectedCustomField( undefined );

		recordEvent( 'product_custom_fields_cancel_edit_modal', {
			source: TRACKS_SOURCE,
		} );
	}

	return (
		<>
			{ renderActionButtonsWrapper(
				<Button variant="secondary" onClick={ handleAddNewButtonClick }>
					{ __( 'Add new', 'woocommerce' ) }
				</Button>
			) }

			{ customFields.length === 0 ? (
				<EmptyState />
			) : (
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

									<Button
										icon={ closeSmall }
										onClick={ customFieldRemoveButtonClickHandler(
											customField
										) }
										aria-label={ __(
											'Remove custom field',
											'woocommerce'
										) }
									/>
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }

			{ showCreateModal && (
				<CreateModal
					values={ customFields }
					onCreate={ handleCreateModalCreate }
					onCancel={ handleCreateModalCancel }
				/>
			) }

			{ selectedCustomField && (
				<EditModal
					initialValue={ selectedCustomField }
					values={ customFields }
					onUpdate={ handleEditModalUpdate }
					onCancel={ handleEditModalCancel }
				/>
			) }
		</>
	);
}
