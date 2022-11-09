/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { trash } from '@wordpress/icons';
import {
	Form,
	__experimentalSelectControlMenuSlot as SelectControlMenuSlot,
} from '@woocommerce/components';

import {
	Button,
	Modal,
	Notice,
	// @ts-expect-error ConfirmDialog is not part of the typescript definition yet.
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import './add-attribute-modal.scss';
import { AttributeInputField } from '../attribute-input-field';
import { AttributeTermInputField } from '../attribute-term-input-field';
import { HydratedAttributeType } from '../attribute-field';

type AddAttributeModalProps = {
	onCancel: () => void;
	onAdd: ( newCategories: HydratedAttributeType[] ) => void;
	selectedAttributeIds?: number[];
};

type AttributeForm = {
	attributes: Array< HydratedAttributeType | { id: undefined; terms: [] } >;
};

export const AddAttributeModal: React.FC< AddAttributeModalProps > = ( {
	onCancel,
	onAdd,
	selectedAttributeIds = [],
} ) => {
	const [ showConfirmClose, setShowConfirmClose ] = useState( false );
	const addAnother = (
		values: AttributeForm,
		setValue: (
			name: string,
			value: AttributeForm[ keyof AttributeForm ]
		) => void
	) => {
		setValue( 'attributes', [
			...values.attributes,
			{
				id: undefined,
				terms: [],
			},
		] );
	};

	const onAddingAttributes = ( values: AttributeForm ) => {
		const newAttributesToAdd: HydratedAttributeType[] = [];
		values.attributes.forEach( ( attr ) => {
			if ( attr.id && attr.name && ( attr.terms || [] ).length > 0 ) {
				newAttributesToAdd.push( {
					...( attr as HydratedAttributeType ),
				} );
			}
		} );
		onAdd( newAttributesToAdd );
	};

	const onRemove = (
		index: number,
		values: AttributeForm,
		setValue: (
			name: string,
			value: AttributeForm[ keyof AttributeForm ]
		) => void
	) => {
		if ( values.attributes.length > 1 ) {
			setValue(
				'attributes',
				values.attributes.filter( ( val, i ) => i !== index )
			);
		} else {
			setValue( `attributes[${ index }]`, [
				{ id: undefined, terms: [] },
			] );
		}
	};

	const focusValueField = ( index: number ) => {
		const valueInputField: HTMLInputElement | null = document.querySelector(
			'.woocommerce-add-attribute-modal__table-row-' +
				index +
				' .woocommerce-add-attribute-modal__table-attribute-value-column .woocommerce-experimental-select-control__input'
		);
		if ( valueInputField ) {
			setTimeout( () => {
				valueInputField.focus();
			}, 0 );
		}
	};

	const onClose = ( values: AttributeForm ) => {
		const hasValuesSet = values.attributes.some(
			( value ) => value?.id && value?.terms && value?.terms.length > 0
		);
		if ( hasValuesSet ) {
			setShowConfirmClose( true );
		} else {
			onCancel();
		}
	};

	return (
		<>
			<Form< AttributeForm >
				initialValues={ {
					attributes: [ { id: undefined, terms: [] } ],
				} }
			>
				{ ( {
					values,
					setValue,
				}: {
					values: AttributeForm;
					// eslint-disable-next-line @typescript-eslint/no-explicit-any
					setValue: ( name: string, value: any ) => void;
				} ) => {
					return (
						<Modal
							title={ __( 'Add attributes', 'woocommerce' ) }
							onRequestClose={ (
								event:
									| React.KeyboardEvent< Element >
									| React.MouseEvent< Element >
									| React.FocusEvent< Element >
							) => {
								if ( ! event.isPropagationStopped() ) {
									onClose( values );
								}
							} }
							className="woocommerce-add-attribute-modal"
						>
							<Notice isDismissible={ false }>
								<p>
									{ __(
										'By default, attributes are filterable and visible on the product page. You can change these settings for each attribute separately later.',
										'woocommerce'
									) }
								</p>
							</Notice>

							<div className="woocommerce-add-attribute-modal__body">
								<table className="woocommerce-add-attribute-modal__table">
									<thead>
										<tr className="woocommerce-add-attribute-modal__table-header">
											<th>Attribute</th>
											<th>Values</th>
										</tr>
									</thead>
									<tbody>
										{ values.attributes.map(
											( formAttr, index ) => (
												<tr
													key={ index }
													className={ `woocommerce-add-attribute-modal__table-row woocommerce-add-attribute-modal__table-row-${ index }` }
												>
													<td className="woocommerce-add-attribute-modal__table-attribute-column">
														<AttributeInputField
															placeholder={ __(
																'Search or create attribute',
																'woocommerce'
															) }
															value={
																formAttr.id &&
																formAttr.name
																	? formAttr
																	: null
															}
															onChange={ (
																val
															) => {
																setValue(
																	'attributes[' +
																		index +
																		']',
																	{
																		...val,
																		terms: [],
																		options:
																			undefined,
																	}
																);
																if ( val ) {
																	focusValueField(
																		index
																	);
																}
															} }
															ignoredAttributeIds={ [
																...selectedAttributeIds,
																...values.attributes
																	.map(
																		(
																			attr
																		) =>
																			attr?.id
																	)
																	.filter(
																		(
																			attrId
																		): attrId is number =>
																			attrId !==
																			undefined
																	),
															] }
														/>
													</td>
													<td className="woocommerce-add-attribute-modal__table-attribute-value-column">
														<AttributeTermInputField
															placeholder={ __(
																'Search or create value',
																'woocommerce'
															) }
															disabled={
																! formAttr.id
															}
															attributeId={
																formAttr.id
															}
															value={
																formAttr.terms
															}
															onChange={ (
																val
															) =>
																setValue(
																	'attributes[' +
																		index +
																		'].terms',
																	val
																)
															}
														/>
													</td>
													<td className="woocommerce-add-attribute-modal__table-attribute-trash-column">
														<Button
															icon={ trash }
															disabled={
																values
																	.attributes
																	.length ===
																	1 &&
																! values
																	.attributes[ 0 ]
																	?.id
															}
															label={ __(
																'Remove attribute',
																'woocommerce'
															) }
															onClick={ () =>
																onRemove(
																	index,
																	values,
																	setValue
																)
															}
														></Button>
													</td>
												</tr>
											)
										) }
									</tbody>
								</table>
							</div>
							<div>
								<Button
									className="woocommerce-add-attribute-modal__add-attribute"
									variant="tertiary"
									label={ __(
										'Add another attribute',
										'woocommerce'
									) }
									onClick={ () =>
										addAnother( values, setValue )
									}
								>
									+&nbsp;
									{ __( 'Add another', 'woocommerce' ) }
								</Button>
							</div>
							<div className="woocommerce-add-attribute-modal__buttons">
								<Button
									isSecondary
									label={ __( 'Cancel', 'woocommerce' ) }
									onClick={ () => onClose( values ) }
								>
									{ __( 'Cancel', 'woocommerce' ) }
								</Button>
								<Button
									isPrimary
									label={ __(
										'Add attributes',
										'woocommerce'
									) }
									disabled={
										values.attributes.length === 1 &&
										! values.attributes[ 0 ]?.id &&
										values.attributes[ 0 ]?.terms
											?.length === 0
									}
									onClick={ () =>
										onAddingAttributes( values )
									}
								>
									{ __( 'Add', 'woocommerce' ) }
								</Button>
							</div>
						</Modal>
					);
				} }
			</Form>
			{ /* Add slot so select control menu renders correctly within Modal */ }
			<SelectControlMenuSlot />
			{ showConfirmClose && (
				<ConfirmDialog
					cancelButtonText={ __( 'No thanks', 'woocommerce' ) }
					confirmButtonText={ __( 'Yes please!', 'woocommerce' ) }
					onCancel={ () => setShowConfirmClose( false ) }
					onConfirm={ onCancel }
				>
					{ __(
						'You have some attributes added to the list, are you sure you want to cancel?',
						'woocommerce'
					) }
				</ConfirmDialog>
			) }
		</>
	);
};
