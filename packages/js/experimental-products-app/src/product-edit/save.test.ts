/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import { saveSelectedProducts } from './save';

const mockGetEditedEntityRecord = jest.fn();
const mockGetEntityRecord = jest.fn();

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

jest.mock( '@wordpress/core-data', () => ( {
	store: {},
} ) );

jest.mock( '@wordpress/data', () => ( {
	select: jest.fn( () => ( {
		getEditedEntityRecord: mockGetEditedEntityRecord,
		getEntityRecord: mockGetEntityRecord,
	} ) ),
} ) );

describe( 'saveSelectedProducts', () => {
	const buildProduct = (
		overrides: Partial< ProductEntityRecord > = {}
	): ProductEntityRecord =>
		( {
			id: 10,
			name: 'Hoodie',
			status: 'draft',
			type: 'simple',
			virtual: false,
			downloadable: false,
			on_sale: false,
			categories: [],
			tags: [],
			images: [],
			...overrides,
		} ) as unknown as ProductEntityRecord;

	const buildVariation = (
		overrides: Partial< ProductEntityRecord > = {}
	): ProductEntityRecord =>
		buildProduct( {
			id: 100,
			parent_id: 10,
			name: 'Blue',
			type: 'variation',
			...overrides,
		} );

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'sends variation image updates using the variation REST image field', async () => {
		const editedVariation = buildVariation( {
			id: 101,
			images: [
				{
					id: 55,
					src: 'https://example.com/blue.jpg',
					alt: 'Blue',
					name: 'Blue image',
					thumbnail: 'https://example.com/blue-thumbnail.jpg',
					date_created: '',
					date_created_gmt: '',
					date_modified: '',
					date_modified_gmt: '',
				},
			],
		} );
		const editedParent = buildProduct( {
			id: 10,
			type: 'variable',
			_embedded: {
				variations: [ editedVariation ],
			},
		} );
		const editEntityRecord = jest.fn(
			(
				_kind,
				_name,
				_recordId,
				edits: Partial< ProductEntityRecord >
			) => {
				Object.assign( editedParent, edits );
			}
		);
		const saveEditedEntityRecord = jest.fn( async () => editedParent );

		mockGetEditedEntityRecord.mockImplementation( ( _kind, _name, id ) =>
			id === editedParent.id ? editedParent : undefined
		);
		( apiFetch as unknown as jest.Mock ).mockResolvedValueOnce( {
			id: 101,
			parent_id: 10,
			name: 'Blue saved',
			image: {
				id: 55,
				src: 'https://example.com/blue.jpg',
				alt: 'Blue',
				name: 'Blue image',
			},
			manage_stock: false,
		} );

		await saveSelectedProducts( {
			selectedProducts: [ editedVariation ],
			editEntityRecord,
			saveEditedEntityRecord,
		} );

		const request = ( apiFetch as unknown as jest.Mock ).mock
			.calls[ 0 ][ 0 ];

		expect( request ).toEqual(
			expect.objectContaining( {
				path: '/wc/v3/products/10/variations/101',
				method: 'PUT',
				data: expect.objectContaining( {
					image: expect.objectContaining( {
						id: 55,
						src: 'https://example.com/blue.jpg',
						alt: 'Blue',
						name: 'Blue image',
					} ),
				} ),
			} )
		);
		expect( request.data.images ).toBeUndefined();
		expect( request.data.image.thumbnail ).toBeUndefined();
	} );

	it( 'sends an empty variation image object when removing a variation image', async () => {
		const editedVariation = buildVariation( {
			id: 101,
			images: [],
		} );
		const editedParent = buildProduct( {
			id: 10,
			type: 'variable',
			_embedded: {
				variations: [ editedVariation ],
			},
		} );
		const editEntityRecord = jest.fn(
			(
				_kind,
				_name,
				_recordId,
				edits: Partial< ProductEntityRecord >
			) => {
				Object.assign( editedParent, edits );
			}
		);
		const saveEditedEntityRecord = jest.fn( async () => editedParent );

		mockGetEditedEntityRecord.mockImplementation( ( _kind, _name, id ) =>
			id === editedParent.id ? editedParent : undefined
		);
		( apiFetch as unknown as jest.Mock ).mockResolvedValueOnce( {
			id: 101,
			parent_id: 10,
			name: 'Blue saved',
			image: null,
			manage_stock: false,
		} );

		await saveSelectedProducts( {
			selectedProducts: [ editedVariation ],
			editEntityRecord,
			saveEditedEntityRecord,
		} );

		const request = ( apiFetch as unknown as jest.Mock ).mock
			.calls[ 0 ][ 0 ];

		expect( request.data ).toEqual(
			expect.objectContaining( {
				image: {},
			} )
		);
		expect( request.data.images ).toBeUndefined();
	} );

	it( 'omits variation cost of goods when the defined value is null', async () => {
		const editedVariation = buildVariation( {
			id: 101,
			cost_of_goods_sold: {
				values: [
					{
						defined_value: null,
						effective_value: '5.00',
					},
				],
			},
		} );
		const editedParent = buildProduct( {
			id: 10,
			type: 'variable',
			_embedded: {
				variations: [ editedVariation ],
			},
		} );
		const editEntityRecord = jest.fn(
			(
				_kind,
				_name,
				_recordId,
				edits: Partial< ProductEntityRecord >
			) => {
				Object.assign( editedParent, edits );
			}
		);
		const saveEditedEntityRecord = jest.fn( async () => editedParent );

		mockGetEditedEntityRecord.mockImplementation( ( _kind, _name, id ) =>
			id === editedParent.id ? editedParent : undefined
		);
		( apiFetch as unknown as jest.Mock ).mockResolvedValueOnce( {
			id: 101,
			parent_id: 10,
			name: 'Blue saved',
			manage_stock: false,
		} );

		await saveSelectedProducts( {
			selectedProducts: [ editedVariation ],
			editEntityRecord,
			saveEditedEntityRecord,
		} );

		const request = ( apiFetch as unknown as jest.Mock ).mock
			.calls[ 0 ][ 0 ];

		expect( request.data.cost_of_goods_sold ).toBeUndefined();
	} );

	it( 'keeps edits for selected variations that failed after another variation saved', async () => {
		const originalSavedVariation = buildVariation( {
			id: 101,
			name: 'Blue original',
		} );
		const originalFailedVariation = buildVariation( {
			id: 102,
			name: 'Green original',
		} );
		const originalUnselectedVariation = buildVariation( {
			id: 103,
			name: 'Red original',
		} );
		const editedSavedVariation = {
			...originalSavedVariation,
			name: 'Blue edited',
		};
		const editedFailedVariation = {
			...originalFailedVariation,
			name: 'Green edited',
		};
		const editedParent = buildProduct( {
			id: 10,
			type: 'variable',
			_embedded: {
				variations: [
					editedSavedVariation,
					editedFailedVariation,
					originalUnselectedVariation,
				],
			},
		} );
		const saveError = new Error( 'Variation save failed.' );
		const editEntityRecord = jest.fn(
			(
				_kind,
				_name,
				_recordId,
				edits: Partial< ProductEntityRecord >
			) => {
				Object.assign( editedParent, edits );
			}
		);
		const saveEditedEntityRecord = jest.fn( async () => editedParent );

		mockGetEditedEntityRecord.mockImplementation( ( _kind, _name, id ) =>
			id === editedParent.id ? editedParent : undefined
		);
		mockGetEntityRecord.mockReturnValue( undefined );
		( apiFetch as unknown as jest.Mock )
			.mockResolvedValueOnce( {
				id: 101,
				parent_id: 10,
				name: 'Blue saved',
				manage_stock: false,
			} )
			.mockRejectedValueOnce( saveError );

		const results = await saveSelectedProducts( {
			selectedProducts: [ editedSavedVariation, editedFailedVariation ],
			editEntityRecord,
			saveEditedEntityRecord,
		} );

		expect( saveEditedEntityRecord ).toHaveBeenCalledWith(
			'root',
			'product',
			10,
			{
				throwOnError: true,
			}
		);
		expect( editedParent._embedded?.variations ).toEqual( [
			expect.objectContaining( {
				id: 101,
				name: 'Blue saved',
			} ),
			expect.objectContaining( {
				id: 102,
				name: 'Green edited',
			} ),
			expect.objectContaining( {
				id: 103,
				name: 'Red original',
			} ),
		] );
		expect( results ).toEqual( [
			expect.objectContaining( {
				status: 'fulfilled',
			} ),
			{
				status: 'rejected',
				reason: saveError,
			},
		] );
	} );
} );
