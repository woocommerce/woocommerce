/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, Notice } from '@wordpress/components';
import { resolveSelect } from '@wordpress/data';
import { trash } from '@wordpress/icons';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	ProductAttribute,
	ProductAttributeTerm,
} from '@woocommerce/data';
import { Form } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './add-attribute-modal.scss';
import { AsyncSelectControl } from '~/products/shared/async-select-control';

type CreateCategoryModalProps = {
	onCancel: () => void;
	onCreated: ( newCategories: ProductAttribute[] ) => void;
	selectedAttributeIds?: number[];
};

type AttributeForm = {
	attributes: {
		attribute: Partial< ProductAttribute >;
		terms: ProductAttributeTerm[];
	}[];
};

export const AddAttributeModal: React.FC< CreateCategoryModalProps > = ( {
	onCancel,
	onCreated,
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
				attribute: {},
				terms: [],
			},
		] );
	};

	const onAdd = ( values: AttributeForm ) => {
		const newAttributesToAdd: ProductAttribute[] = [];
		values.attributes.forEach( ( attr, index ) => {
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
		onCreated( newAttributesToAdd );
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
			setValue( 'attributes', [ { attribute: {}, terms: [] } ] );
		}
	};

	const getFilteredItems = (
		allItems: Partial< ProductAttribute >[],
		inputValue: string,
		selectedItems: AttributeForm
	) => {
		return allItems.filter(
			( item ) =>
				selectedItems.attributes.findIndex(
					( attr ) => attr.attribute.id === item.id
				) < 0 &&
				selectedAttributeIds.indexOf( item.id || -1 ) < 0 &&
				( item.name || '' )
					.toLowerCase()
					.startsWith( inputValue.toLowerCase() )
		);
	};

	const focusValueField = ( index: number ) => {
		const valueInputField: HTMLInputElement | null = document.querySelector(
			'.woocommerce-add-attribute-modal__table-row-' +
				index +
				' .woocommerce-add-attribute-modal__table-attribute-value-column .woocommerce-experimental-select-control__input'
		);
		if ( valueInputField ) {
			valueInputField.focus();
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
					attributes: [ { attribute: {}, terms: [] } ],
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
													<AsyncSelectControl<
														Partial< ProductAttribute >
													>
														items={ [] }
														label=""
														onSearch={ (
															searchString:
																| string
																| undefined
														) => {
															return resolveSelect(
																EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
															)
																.getProductAttributes<
																	ProductAttribute[]
																>()
																.then(
																	(
																		categories
																	) => {
																		return getFilteredItems(
																			categories,
																			searchString ||
																				'',
																			values
																		);
																	}
																);
														} }
														placeholder={ __(
															'Search or create attribute',
															'woocommerce'
														) }
														getItemLabel={ (
															item
														) => item?.name || '' }
														getItemValue={ (
															item
														) => item?.id || '' }
														selected={ attribute }
														onSelect={ ( item ) => {
															setValue(
																'attributes[' +
																	index +
																	']',
																{
																	attribute:
																		item,
																	terms: [],
																}
															);
															focusValueField(
																index
															);
														} }
														onRemove={ () =>
															setValue(
																'attributes[' +
																	index +
																	']',
																{
																	attribute:
																		{},
																	terms: [],
																}
															)
														}
													/>
												</td>
												<td className="woocommerce-add-attribute-modal__table-attribute-value-column">
													<AsyncSelectControl< ProductAttributeTerm >
														items={ [] }
														multiple
														disabled={
															! attribute?.id
														}
														label=""
														triggerInitialFetch={
															false
														}
														onSearch={ (
															searchString:
																| string
																| undefined
														) => {
															return resolveSelect(
																EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
															).getProductAttributeTerms<
																ProductAttributeTerm[]
															>( {
																search: searchString,
																attribute_id:
																	attribute.id,
															} );
														} }
														placeholder={ __(
															'Search or create value',
															'woocommerce'
														) }
														getItemLabel={ (
															item
														) => item?.name || '' }
														getItemValue={ (
															item
														) => item?.slug || '' }
														selected={ terms || [] }
														onSelect={ ( item ) => {
															setValue(
																'attributes[' +
																	index +
																	'].terms',
																[
																	...( terms ||
																		[] ),
																	item,
																]
															);
														} }
														onRemove={ ( item ) =>
															setValue(
																'attributes[' +
																	index +
																	'].terms',
																values.attributes[
																	index
																].terms.filter(
																	( opt ) =>
																		opt.slug !==
																		item.slug
																)
															)
														}
													/>
												</td>
												<td>
													<Button
														icon={ trash }
														disabled={
															values.attributes
																.length === 1 &&
															! values
																.attributes[ 0 ]
																.attribute.id
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
							<Button
								variant="tertiary"
								onClick={ () => addAnother( values, setValue ) }
							>
								+&nbsp;{ __( 'Add another', 'woocommerce' ) }
							</Button>
							<div className="woocommerce-add-attribute-modal__wrapper">
								<div className="woocommerce-add-attribute-modal__buttons">
									<Button
										isSecondary
										onClick={ () => onCancel() }
									>
										{ __( 'Cancel', 'woocommerce' ) }
									</Button>
									<Button
										isPrimary
										disabled={
											values.attributes.length === 1 &&
											! values.attributes[ 0 ].attribute
												.id &&
											values.attributes[ 0 ].terms
												.length === 0
										}
										onClick={ () => onAdd( values ) }
									>
										{ __( 'Add', 'woocommerce' ) }
									</Button>
								</div>
							</div>
						</>
					);
				} }
			</Form>
		</Modal>
	);
};
