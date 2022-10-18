/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, Notice } from '@wordpress/components';
import { trash } from '@wordpress/icons';
import { ProductAttribute, ProductAttributeTerm } from '@woocommerce/data';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './add-attribute-modal.scss';
import { AttributeInputField } from '../attribute-input-field';
import { AttributeTermInputField } from '../attribute-term-input-field';

type CreateCategoryModalProps = {
	onCancel: () => void;
	onAdd: ( newCategories: ProductAttribute[] ) => void;
	selectedAttributeIds?: number[];
};

type AttributeForm = {
	attributes: {
		attribute?: ProductAttribute;
		terms: ProductAttributeTerm[];
	}[];
};

export const AddAttributeModal: React.FC< CreateCategoryModalProps > = ( {
	onCancel,
	onAdd,
	selectedAttributeIds = [],
} ) => {
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
				attribute: undefined,
				terms: [],
			},
		] );
	};

	const onAddingAttributes = ( values: AttributeForm ) => {
		const newAttributesToAdd: ProductAttribute[] = [];
		values.attributes.forEach( ( attr ) => {
			if (
				attr.attribute &&
				attr.attribute.name &&
				attr.terms.length > 0
			) {
				newAttributesToAdd.push( {
					...( attr.attribute as ProductAttribute ),
					options: attr.terms.map( ( term ) => term.name ),
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
				{ attribute: undefined, terms: [] },
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

	return (
		<Modal
			title={ __( 'Add attributes', 'woocommerce' ) }
			onRequestClose={ () => onCancel() }
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
			<Form< AttributeForm >
				initialValues={ {
					attributes: [ { attribute: undefined, terms: [] } ],
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
						<>
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
											( { attribute, terms }, index ) => (
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
															value={ attribute }
															onChange={ (
																val
															) => {
																setValue(
																	'attributes[' +
																		index +
																		'].attribute',
																	val
																);
																if ( val ) {
																	focusValueField(
																		index
																	);
																}
															} }
															filteredAttributeIds={ [
																...selectedAttributeIds,
																...values.attributes
																	.map(
																		(
																			attr
																		) =>
																			attr
																				?.attribute
																				?.id
																	)
																	.filter(
																		(
																			id
																		): id is number =>
																			id !==
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
																! attribute?.id
															}
															attributeId={
																attribute?.id
															}
															value={ terms }
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
													<td>
														<Button
															icon={ trash }
															disabled={
																values
																	.attributes
																	.length ===
																	1 &&
																! values
																	.attributes[ 0 ]
																	?.attribute
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
									onClick={ () => onCancel() }
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
										! values.attributes[ 0 ]?.attribute
											?.id &&
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
						</>
					);
				} }
			</Form>
		</Modal>
	);
};
