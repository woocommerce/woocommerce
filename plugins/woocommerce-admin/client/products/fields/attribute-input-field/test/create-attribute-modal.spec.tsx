/**
 * External dependencies
 */
import { fireEvent, render, waitFor } from '@testing-library/react';
import { act } from '@testing-library/react-hooks';
import { useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CreateAttributeModal } from '../create-attribute-modal';

jest.mock( '@woocommerce/tracks' );
jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useDispatch: jest.fn().mockReturnValue( {
		createNotice: () => {},
		createProductAttribute: () => {},
		invalidateResolutionForStoreSelector: () => {},
	} ),
} ) );

describe( 'CreateAttributeModal', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should show 4 fields name, slug, enable archives, and sort order', () => {
		const { queryByLabelText } = render(
			<CreateAttributeModal
				initialAttributeName="Random new attribute"
				onCancel={ jest.fn() }
				onCreated={ jest.fn() }
			/>
		);
		expect( queryByLabelText( 'Name' ) ).toBeInTheDocument();
		expect( queryByLabelText( 'Slug' ) ).toBeInTheDocument();
		expect( queryByLabelText( 'Enable Archives?' ) ).toBeInTheDocument();
		expect( queryByLabelText( 'Default sort order' ) ).toBeInTheDocument();
	} );

	it( 'should auto fill the name and slug with the initialAttributeName', () => {
		const { getByLabelText } = render(
			<CreateAttributeModal
				initialAttributeName="Random new attribute"
				onCancel={ jest.fn() }
				onCreated={ jest.fn() }
			/>
		);
		expect( getByLabelText( 'Name' ) ).toHaveValue(
			'Random new attribute'
		);
		expect( getByLabelText( 'Slug' ) ).toHaveValue(
			'random-new-attribute'
		);
	} );

	it( 'should update slug after name field has blurred', () => {
		const { getByLabelText } = render(
			<CreateAttributeModal
				initialAttributeName="Random new attribute"
				onCancel={ jest.fn() }
				onCreated={ jest.fn() }
			/>
		);
		const nameInput = getByLabelText( 'Name' );
		fireEvent.change( nameInput, {
			target: { value: 'Random new attribute UPDATED' },
		} );
		fireEvent.blur( nameInput );
		expect( getByLabelText( 'Slug' ) ).toHaveValue(
			'random-new-attribute-updated'
		);
	} );

	it( 'should call createProductAttribute when add is clicked', async () => {
		const createProductAttributeMock = jest.fn().mockResolvedValue( {
			name: 'new attribute',
		} );
		( useDispatch as jest.Mock ).mockReturnValue( {
			createNotice: jest.fn(),
			createProductAttribute: createProductAttributeMock,
			invalidateResolutionForStoreSelector: jest.fn(),
		} );
		const { getByLabelText } = render(
			<CreateAttributeModal
				initialAttributeName="Random new attribute"
				onCancel={ jest.fn() }
				onCreated={ jest.fn() }
			/>
		);
		const addButton = getByLabelText( 'Add attribute' );
		await act( () => {
			addButton.click();
		} );
		await waitFor( () => {
			expect( createProductAttributeMock ).toHaveBeenCalledWith( {
				name: 'Random new attribute',
				slug: 'random-new-attribute',
			} );
		} );
	} );

	it( 'should trigger product_attribute_event when add is clicked', async () => {
		const createProductAttributeMock = jest.fn().mockResolvedValue( {
			name: 'new attribute',
		} );
		( useDispatch as jest.Mock ).mockReturnValue( {
			createNotice: jest.fn(),
			createProductAttribute: createProductAttributeMock,
			invalidateResolutionForStoreSelector: jest.fn(),
		} );
		const { getByLabelText } = render(
			<CreateAttributeModal
				initialAttributeName="Random new attribute"
				onCancel={ jest.fn() }
				onCreated={ jest.fn() }
			/>
		);
		const addButton = getByLabelText( 'Add attribute' );
		await act( () => {
			addButton.click();
		} );
		await waitFor( () => {
			expect( recordEvent ).toHaveBeenCalledWith(
				'product_attribute_add',
				{
					new_product_page: true,
				}
			);
		} );
	} );

	it( 'should trigger onCreated with the new attribute if succeeded', async () => {
		const createProductAttributeMock = jest.fn().mockResolvedValue( {
			name: 'new attribute',
		} );
		const onCreatedMock = jest.fn();
		( useDispatch as jest.Mock ).mockReturnValue( {
			createNotice: jest.fn(),
			createProductAttribute: createProductAttributeMock,
			invalidateResolutionForStoreSelector: jest.fn(),
		} );
		const { getByLabelText } = render(
			<CreateAttributeModal
				initialAttributeName="Random new attribute"
				onCancel={ jest.fn() }
				onCreated={ onCreatedMock }
			/>
		);
		const addButton = getByLabelText( 'Add attribute' );
		await act( () => {
			addButton.click();
		} );
		await waitFor( () => {
			expect( onCreatedMock ).toHaveBeenCalledWith( {
				name: 'new attribute',
			} );
		} );
	} );

	it( 'should trigger a error notice and onCancel if createProductAttribute fails', async () => {
		const createProductAttributeMock = jest
			.fn()
			.mockRejectedValue( new Error( 'Async error' ) );
		const onCancelMock = jest.fn();
		const onCreatedMock = jest.fn();
		const createNoticeMock = jest.fn();
		( useDispatch as jest.Mock ).mockReturnValue( {
			createNotice: createNoticeMock,
			createProductAttribute: createProductAttributeMock,
			invalidateResolutionForStoreSelector: jest.fn(),
		} );
		const { getByLabelText } = render(
			<CreateAttributeModal
				initialAttributeName="Random new attribute"
				onCancel={ onCancelMock }
				onCreated={ onCreatedMock }
			/>
		);
		const addButton = getByLabelText( 'Add attribute' );
		await act( () => {
			addButton.click();
		} );
		await waitFor( () => {
			expect( onCreatedMock ).not.toHaveBeenCalled();
			expect( onCancelMock ).toHaveBeenCalled();
			expect( createNoticeMock ).toHaveBeenCalledWith(
				'error',
				'Failed to create attribute.'
			);
		} );
	} );

	it( 'should invalidate the getProductAttributes store when created successfully', async () => {
		const createProductAttributeMock = jest.fn().mockResolvedValue( {
			name: 'new attribute',
		} );
		const invalidateResolutionForStoreSelectorMock = jest.fn();
		( useDispatch as jest.Mock ).mockReturnValue( {
			createNotice: jest.fn(),
			createProductAttribute: createProductAttributeMock,
			invalidateResolutionForStoreSelector:
				invalidateResolutionForStoreSelectorMock,
		} );
		const { getByLabelText } = render(
			<CreateAttributeModal
				initialAttributeName="Random new attribute"
				onCancel={ jest.fn() }
				onCreated={ jest.fn() }
			/>
		);
		const addButton = getByLabelText( 'Add attribute' );
		await act( () => {
			addButton.click();
		} );
		await waitFor( () => {
			expect(
				invalidateResolutionForStoreSelectorMock
			).toHaveBeenCalledWith( 'getProductAttributes' );
		} );
	} );
} );
