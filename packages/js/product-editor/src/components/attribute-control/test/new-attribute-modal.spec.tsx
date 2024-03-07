/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { ProductAttribute, ProductAttributeTerm } from '@woocommerce/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { NewAttributeModal } from '../new-attribute-modal';

let attributeOnChange: ( val: ProductAttribute ) => void;
jest.mock( '../../attribute-input-field', () => ( {
	AttributeInputField: ( {
		onChange,
	}: {
		onChange: (
			value?: Omit<
				ProductAttribute,
				'position' | 'visible' | 'variation'
			>
		) => void;
	} ) => {
		attributeOnChange = onChange;
		return <div>attribute_input_field</div>;
	},
} ) );
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

const attributeList: ProductAttribute[] = [
	{
		id: 15,
		name: 'Automotive',
		position: 0,
		slug: 'Automotive',
		visible: true,
		variation: false,
		options: [ 'test' ],
	},
	{
		id: 1,
		name: 'Color',
		slug: 'Color',
		position: 2,
		visible: true,
		variation: true,
		options: [
			'Beige',
			'black',
			'Blue',
			'brown',
			'Gray',
			'Green',
			'mint',
			'orange',
			'pink',
			'Red',
			'white',
			'Yellow',
		],
	},
];

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
		expect( queryAllByText( 'attribute_input_field' ).length ).toEqual( 1 );
		expect(
			queryAllByText( 'attribute_term_input_field: disabled:true' ).length
		).toEqual( 1 );
	} );

	it( 'should enable attribute term field once attribute is selected', () => {
		const { queryAllByText } = render(
			<NewAttributeModal
				onCancel={ () => {} }
				onAdd={ () => {} }
				selectedAttributeIds={ [] }
			/>
		);
		expect( queryAllByText( 'attribute_input_field' ).length ).toEqual( 1 );
		attributeOnChange( attributeList[ 0 ] );
		expect(
			queryAllByText( 'attribute_term_input_field: disabled:false' )
				.length
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
		expect( queryAllByText( 'attribute_input_field' ).length ).toEqual( 2 );
		expect(
			queryAllByText( 'attribute_term_input_field: disabled:true' ).length
		).toEqual( 2 );
		queryByRole( 'button', { name: 'Add another attribute' } )?.click();
		expect( queryAllByText( 'attribute_input_field' ).length ).toEqual( 3 );
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
		expect( queryAllByText( 'attribute_input_field' ).length ).toEqual( 3 );
		expect(
			queryAllByText( 'attribute_term_input_field: disabled:true' ).length
		).toEqual( 3 );

		const removeButtons = queryAllByLabelText( 'Remove attribute' );

		removeButtons[ 0 ].click();
		removeButtons[ 1 ].click();
		expect( queryAllByText( 'attribute_input_field' ).length ).toEqual( 1 );
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
		expect( queryAllByText( 'attribute_input_field' ).length ).toEqual( 1 );
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
			expect( queryAllByText( 'attribute_input_field' ).length ).toEqual(
				3
			);
			expect(
				queryAllByText( 'attribute_term_input_field: disabled:true' )
					.length
			).toEqual( 3 );
			queryByRole( 'button', { name: 'Add attributes' } )?.click();
			expect( onAddMock ).toHaveBeenCalledWith( [] );
		} );

		it( 'should not add attribute if no terms were selected', () => {
			const onAddMock = jest.fn();
			const { queryByRole } = render(
				<NewAttributeModal
					onCancel={ () => {} }
					onAdd={ onAddMock }
					selectedAttributeIds={ [] }
				/>
			);

			attributeOnChange( {
				...attributeList[ 0 ],
				options: [],
			} );
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

			attributeOnChange( attributeList[ 0 ] );
			attributeTermOnChange( [
				attributeTermList[ 0 ],
				attributeTermList[ 1 ],
			] );
			queryByRole( 'button', { name: 'Add attributes' } )?.click();

			const onAddMockCalls = onAddMock.mock.calls[ 0 ][ 0 ];

			expect( onAddMockCalls ).toHaveLength( 1 );
			expect( onAddMockCalls[ 0 ].id ).toEqual( attributeList[ 0 ].id );
			expect( onAddMockCalls[ 0 ].terms[ 0 ].name ).toEqual(
				attributeTermList[ 0 ].name
			);
			expect( onAddMockCalls[ 0 ].terms[ 1 ].name ).toEqual(
				attributeTermList[ 1 ].name
			);
		} );
	} );
} );
