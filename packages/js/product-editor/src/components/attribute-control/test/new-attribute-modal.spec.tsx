/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { ProductAttributeTerm } from '@woocommerce/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { NewAttributeModal } from '../new-attribute-modal';

let attributeTermOnChange: ( val: ProductAttributeTerm[] ) => void;
jest.mock( '../../attribute-term-input-field', () => ( {
	AttributeTermInputField: ( {
		onChange,
		disabled,
	}: {
		onChange: ( value: ProductAttributeTerm[] ) => void;
		disabled: boolean;
	} ) => {
		attributeTermOnChange = onChange;
		return (
			<div>
				attribute_term_input_field: disabled:{ disabled.toString() }
			</div>
		);
	},
} ) );

const attributeTermList: ProductAttributeTerm[] = [
	{
		id: 23,
		name: 'XXS',
		slug: 'xxs',
		description: '',
		menu_order: 1,
		count: 1,
	},
	{
		id: 22,
		name: 'XS',
		slug: 'xs',
		description: '',
		menu_order: 2,
		count: 1,
	},
	{
		id: 17,
		name: 'S',
		slug: 's',
		description: '',
		menu_order: 3,
		count: 1,
	},
	{
		id: 18,
		name: 'M',
		slug: 'm',
		description: '',
		menu_order: 4,
		count: 1,
	},
	{
		id: 19,
		name: 'L',
		slug: 'l',
		description: '',
		menu_order: 5,
		count: 1,
	},
];

describe( 'NewAttributeModal', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render at-least one row with the attribute dropdown fields', () => {
		const { queryAllByText } = render(
			<NewAttributeModal
				onCancel={ () => {} }
				onAdd={ () => {} }
				selectedAttributeIds={ [] }
			/>
		);
		expect(
			queryAllByText( 'attribute_term_input_field: disabled:true' ).length
		).toEqual( 1 );
	} );

	it( 'should allow us to add multiple new rows with the attribute fields', () => {
		const { queryAllByText, queryByRole } = render(
			<NewAttributeModal
				onCancel={ () => {} }
				onAdd={ () => {} }
				selectedAttributeIds={ [] }
			/>
		);
		queryByRole( 'button', { name: 'Add another attribute' } )?.click();

		expect(
			queryAllByText( 'attribute_term_input_field: disabled:true' ).length
		).toEqual( 2 );
		queryByRole( 'button', { name: 'Add another attribute' } )?.click();

		expect(
			queryAllByText( 'attribute_term_input_field: disabled:true' ).length
		).toEqual( 3 );
	} );

	it( 'should allow us to remove the added fields', () => {
		const { queryAllByText, queryByRole, queryAllByLabelText } = render(
			<NewAttributeModal
				onCancel={ () => {} }
				onAdd={ () => {} }
				selectedAttributeIds={ [] }
			/>
		);

		queryByRole( 'button', { name: 'Add another attribute' } )?.click();
		queryByRole( 'button', { name: 'Add another attribute' } )?.click();

		expect(
			queryAllByText( 'attribute_term_input_field: disabled:true' ).length
		).toEqual( 3 );

		const removeButtons = queryAllByLabelText( 'Remove attribute' );

		removeButtons[ 0 ].click();
		removeButtons[ 1 ].click();

		expect(
			queryAllByText( 'attribute_term_input_field: disabled:true' ).length
		).toEqual( 1 );
	} );

	it( 'should not allow us to remove all the rows', () => {
		const { queryAllByText, queryAllByLabelText } = render(
			<NewAttributeModal
				onCancel={ () => {} }
				onAdd={ () => {} }
				selectedAttributeIds={ [] }
			/>
		);

		const removeButtons = queryAllByLabelText( 'Remove attribute' );

		removeButtons[ 0 ].click();

		expect(
			queryAllByText( 'attribute_term_input_field: disabled:true' ).length
		).toEqual( 1 );
	} );

	describe( 'onAdd', () => {
		it( 'should not return empty attribute rows', () => {
			const onAddMock = jest.fn();
			const { queryAllByText, queryByLabelText, queryByRole } = render(
				<NewAttributeModal
					onCancel={ () => {} }
					onAdd={ onAddMock }
					selectedAttributeIds={ [] }
				/>
			);

			const addAnotherButton = queryByLabelText(
				'Add another attribute'
			);
			addAnotherButton?.click();
			addAnotherButton?.click();

			expect(
				queryAllByText( 'attribute_term_input_field: disabled:true' )
					.length
			).toEqual( 3 );
			queryByRole( 'button', { name: 'Add attributes' } )?.click();
			expect( onAddMock ).toHaveBeenCalledWith( [] );
		} );

		it( 'should add attribute with array of terms', () => {
			const onAddMock = jest.fn();
			const { queryByRole } = render(
				<NewAttributeModal
					onCancel={ () => {} }
					onAdd={ onAddMock }
					selectedAttributeIds={ [] }
				/>
			);

			attributeTermOnChange( [
				attributeTermList[ 0 ],
				attributeTermList[ 1 ],
			] );
			queryByRole( 'button', { name: 'Add attributes' } )?.click();
		} );
	} );
} );
