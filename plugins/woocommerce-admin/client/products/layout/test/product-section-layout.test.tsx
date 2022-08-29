/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ProductSectionLayout } from '../product-section-layout';

jest.mock( '../product-field-layout', () => {
	const productFieldLayoutMock: React.FC< {
		fieldName: string;
		categoryName: string;
	} > = ( { children, fieldName, categoryName } ) => {
		return (
			<div className="product-field-layout-mock">
				<span>fieldName: { fieldName }</span>
				<span>categoryName: { categoryName }</span>
				{ children }
			</div>
		);
	};
	return {
		ProductFieldLayout: productFieldLayoutMock,
	};
} );

const SampleInputField: React.FC< { name: string; onChange: () => void } > = ( {
	name,
} ) => {
	return <div>smaple-input-field-{ name }</div>;
};

describe( 'ProductSectionLayout', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should render the title and description', () => {
		const { queryByText } = render(
			<ProductSectionLayout
				title="Title"
				description="This is a description"
			/>
		);
		expect( queryByText( 'Title' ) ).toBeInTheDocument();
		expect( queryByText( 'This is a description' ) ).toBeInTheDocument();
	} );

	it( 'should wrap children in ProductFieldLayout if prop contains onChange', () => {
		const { queryByText, queryAllByText } = render(
			<ProductSectionLayout
				title="Title"
				description="This is a description"
			>
				<SampleInputField name="name" onChange={ () => {} } />
				<SampleInputField name="description" onChange={ () => {} } />
			</ProductSectionLayout>
		);

		expect( queryByText( 'fieldName: name' ) ).toBeInTheDocument();
		expect( queryAllByText( 'categoryName: Title' ).length ).toEqual( 2 );

		expect( queryByText( 'smaple-input-field-name' ) ).toBeInTheDocument();
		expect(
			queryByText( 'smaple-input-field-description' )
		).toBeInTheDocument();
	} );

	it( 'should not wrap children in ProductFieldLayout if prop does not contain onChange', () => {
		const { queryByText, queryAllByText } = render(
			<ProductSectionLayout
				title="Title"
				description="This is a description"
			>
				<div> A child</div>
				<div> Another child</div>
			</ProductSectionLayout>
		);

		expect( queryAllByText( 'categoryName: Title' ).length ).toEqual( 0 );

		expect( queryByText( 'A child' ) ).toBeInTheDocument();
		expect( queryByText( 'Another child' ) ).toBeInTheDocument();
	} );
} );
