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
import { recordEvent } from '@woocommerce/tracks';
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
import {
	AttributeTermInputField,
	CustomAttributeTermInputField,
} from '../attribute-term-input-field';
import { EnhancedProductAttribute } from '~/products/hooks/use-product-attributes';
import { getProductAttributeObject } from './utils';

type AddAttributeModalProps = {
	title?: string;
	notice?: string;
	attributeLabel?: string;
	valueLabel?: string;
	attributePlaceholder?: string;
	termPlaceholder?: string;
	removeLabel?: string;
	addAnotherAccessibleLabel?: string;
	addAnotherLabel?: string;
	cancelLabel?: string;
	addAccessibleLabel?: string;
	addLabel?: string;
	confirmMessage?: string;
	confirmCancelLabel?: string;
	confirmConfirmLabel?: string;
	onCancel: () => void;
	onAdd: ( newCategories: EnhancedProductAttribute[] ) => void;
	selectedAttributeIds?: number[];
};

type AttributeForm = {
	attributes: Array< EnhancedProductAttribute | null >;
};

export const AddAttributeModal: React.FC< AddAttributeModalProps > = ( {
	title = __( 'Add attributes', 'woocommerce' ),
	notice = __(
		'By default, attributes are filterable and visible on the product page. You can change these settings for each attribute separately later.',
		'woocommerce'
	),
	attributeLabel = __( 'Attribute', 'woocommerce' ),
	valueLabel = __( 'Values', 'woocommerce' ),
	attributePlaceholder = __( 'Search or create attribute', 'woocommerce' ),
	termPlaceholder = __( 'Search or create value', 'woocommerce' ),
	removeLabel = __( 'Remove attribute', 'woocommerce' ),
	addAnotherAccessibleLabel = __( 'Add another attribute', 'woocommerce' ),
	addAnotherLabel = __( '+ Add another', 'woocommerce' ),
	cancelLabel = __( 'Cancel', 'woocommerce' ),
	addAccessibleLabel = __( 'Add attributes', 'woocommerce' ),
	addLabel = __( 'Add', 'woocommerce' ),
	confirmMessage = __(
		'You have some attributes added to the list, are you sure you want to cancel?',
		'woocommerce'
	),
	confirmCancelLabel = __( 'No thanks', 'woocommerce' ),
	confirmConfirmLabel = __( 'Yes please!', 'woocommerce' ),
	onCancel,
	onAdd,
	selectedAttributeIds = [],
} ) => {
	const scrollAttributeIntoView = ( index: number ) => {
		setTimeout( () => {
			const attributeRow = document.querySelector(
				`.woocommerce-add-attribute-modal__table-row-${ index }`
			);
			attributeRow?.scrollIntoView( { behavior: 'smooth' } );
		}, 0 );
	};

	const [ showConfirmClose, setShowConfirmClose ] = useState( false );
	const addAnother = (
		values: AttributeForm,
		setValue: (
			name: string,
			value: AttributeForm[ keyof AttributeForm ]
		) => void
	) => {
		setValue( 'attributes', [ ...values.attributes, null ] );
		scrollAttributeIntoView( values.attributes.length );
	};

	const onAddingAttributes = ( values: AttributeForm ) => {
		const newAttributesToAdd: EnhancedProductAttribute[] = [];
		values.attributes.forEach( ( attr ) => {
			if (
				attr !== null &&
				attr.name &&
				( ( attr.terms || [] ).length > 0 ||
					( attr.options || [] ).length > 0 )
			) {
				const options =
					attr.id !== 0
						? ( attr.terms || [] ).map( ( term ) => term.name )
						: attr.options;
				newAttributesToAdd.push( {
					...( attr as EnhancedProductAttribute ),
					options,
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
		recordEvent(
			'product_add_attributes_modal_remove_attribute_button_click'
		);
		if ( values.attributes.length > 1 ) {
			setValue(
				'attributes',
				values.attributes.filter( ( val, i ) => i !== index )
			);
		} else {
			setValue( `attributes[${ index }]`, [ null ] );
		}
	};

	const focusValueField = ( index: number ) => {
		setTimeout( () => {
			const valueInputField: HTMLInputElement | null =
				document.querySelector(
					'.woocommerce-add-attribute-modal__table-row-' +
						index +
						' .woocommerce-add-attribute-modal__table-attribute-value-column .woocommerce-experimental-select-control__input'
				);
			if ( valueInputField ) {
				valueInputField.focus();
			}
		}, 0 );
	};

	const onClose = ( values: AttributeForm ) => {
		const hasValuesSet = values.attributes.some(
			( value ) =>
				value !== null && value?.terms && value?.terms.length > 0
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
					attributes: [ null ],
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
							title={ title }
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
								<p>{ notice }</p>
							</Notice>

							<div className="woocommerce-add-attribute-modal__body">
								<table className="woocommerce-add-attribute-modal__table">
									<thead>
										<tr className="woocommerce-add-attribute-modal__table-header">
											<th>{ attributeLabel }</th>
											<th>{ valueLabel }</th>
										</tr>
									</thead>
									<tbody>
										{ values.attributes.map(
											( attribute, index ) => (
												<tr
													key={ index }
													className={ `woocommerce-add-attribute-modal__table-row woocommerce-add-attribute-modal__table-row-${ index }` }
												>
													<td className="woocommerce-add-attribute-modal__table-attribute-column">
														<AttributeInputField
															placeholder={
																attributePlaceholder
															}
															value={ attribute }
															label={
																attributeLabel
															}
															onChange={ (
																val
															) => {
																setValue(
																	'attributes[' +
																		index +
																		']',
																	val &&
																		getProductAttributeObject(
																			val
																		)
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
														{ attribute === null ||
														attribute.id !== 0 ? (
															<AttributeTermInputField
																placeholder={
																	termPlaceholder
																}
																disabled={
																	attribute
																		? ! attribute.id
																		: true
																}
																attributeId={
																	attribute
																		? attribute.id
																		: undefined
																}
																value={
																	attribute ===
																	null
																		? []
																		: attribute.terms
																}
																label={
																	valueLabel
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
														) : (
															<CustomAttributeTermInputField
																placeholder={
																	termPlaceholder
																}
																disabled={
																	! attribute.name
																}
																value={
																	attribute.options
																}
																label={
																	valueLabel
																}
																onChange={ (
																	val
																) =>
																	setValue(
																		'attributes[' +
																			index +
																			'].options',
																		val
																	)
																}
															/>
														) }
													</td>
													<td className="woocommerce-add-attribute-modal__table-attribute-trash-column">
														<Button
															icon={ trash }
															disabled={
																values
																	.attributes
																	.length ===
																	1 &&
																values
																	.attributes[ 0 ] ===
																	null
															}
															label={
																removeLabel
															}
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
									label={ addAnotherAccessibleLabel }
									onClick={ () => {
										recordEvent(
											'product_add_attributes_modal_add_another_attribute_button_click'
										);
										addAnother( values, setValue );
									} }
								>
									{ addAnotherLabel }
								</Button>
							</div>
							<div className="woocommerce-add-attribute-modal__buttons">
								<Button
									isSecondary
									label={ cancelLabel }
									onClick={ () => onClose( values ) }
								>
									{ cancelLabel }
								</Button>
								<Button
									isPrimary
									label={ addAccessibleLabel }
									disabled={
										values.attributes.length === 1 &&
										values.attributes[ 0 ] === null
									}
									onClick={ () =>
										onAddingAttributes( values )
									}
								>
									{ addLabel }
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
					cancelButtonText={ confirmCancelLabel }
					confirmButtonText={ confirmConfirmLabel }
					onCancel={ () => setShowConfirmClose( false ) }
					onConfirm={ onCancel }
				>
					{ confirmMessage }
				</ConfirmDialog>
			) }
		</>
	);
};
