/**
 * External dependencies
 */
import { getContext } from '@wordpress/interactivity';
import type { ProductResponseItem, CartItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	ScopeState,
	ProductScopeContext,
	ProductScopesState,
} from '../scope';
import type { CatalogState } from '../catalog';
import { findCartLine } from '../cart-actions';

type FakeCartLine = CartItem & {
	variation?: ProductScopeContext[ 'variation' ];
};

type MockState = CatalogState & {
	productScopes: ProductScopesState;
};

let mockContext: ProductScopeContext | null = null;

/**
 * A minimal stand-in for `cart-actions.ts`'s `findCartLine` (which has its
 * own test suite): matches by `key` when given, otherwise by `id` and a
 * shallow `variation` comparison.
 *
 * @param lines The seeded cart lines to match against.
 * @return A function with the same call shape `findCartLine` has.
 */
function fakeFindCartLine( lines: FakeCartLine[] ) {
	return ( {
		id,
		key,
		variation,
	}: {
		id: number;
		key?: string;
		variation?: ProductScopeContext[ 'variation' ];
	} ): FakeCartLine | undefined => {
		return lines.find( ( line ) => {
			if ( key ) {
				return line.key === key;
			}
			return (
				line.id === id &&
				JSON.stringify( line.variation ?? [] ) ===
					JSON.stringify( variation ?? [] )
			);
		} );
	};
}

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		getContext: jest.fn( () => mockContext ),
	} ),
	{ virtual: true }
);

// `scope.ts` calls `cart-actions.ts`'s `findCartLine` directly for
// `cartItem`; each test controls its return value via
// `( findCartLine as jest.Mock ).mockImplementation( ... )`.
jest.mock( '../cart-actions', () => ( {
	findCartLine: jest.fn( () => undefined ),
} ) );

/**
 * A minimal stand-in for the one behaviour of `@wordpress/interactivity`'s
 * real state proxy this module's first-write defect depends on: the first
 * read of a key that does not exist yet freezes what every later read of
 * that key returns, even after a write lands on the underlying raw object
 * rather than through the proxy. A write made through the proxy itself
 * always updates what later reads see, and any nested object value is
 * wrapped the same way, recursively.
 *
 * @param target The raw object to wrap.
 * @return A proxy over `target` with that one behaviour.
 */
function makeReactive< T extends Record< string, unknown > >( target: T ): T {
	const frozen = new Map< string, unknown >();
	const wrap = ( value: unknown ): unknown =>
		value !== null && typeof value === 'object'
			? makeReactive( value as Record< string, unknown > )
			: value;
	return new Proxy( target, {
		get( obj, key ) {
			if ( typeof key !== 'string' ) {
				return Reflect.get( obj, key );
			}
			if ( ! frozen.has( key ) ) {
				frozen.set( key, wrap( Reflect.get( obj, key ) ) );
			}
			return frozen.get( key );
		},
		set( obj, key, value ) {
			if ( typeof key !== 'string' ) {
				return Reflect.set( obj, key, value );
			}
			Reflect.set( obj, key, value );
			frozen.set( key, wrap( value ) );
			return true;
		},
	} ) as T;
}

const mockProduct = ( overrides: Partial< ProductResponseItem > = {} ) =>
	( { id: 1, type: 'simple', ...overrides } ) as ProductResponseItem;

describe( 'woocommerce store — product scope envelope', () => {
	let scopeState: ScopeState;
	let mockState: MockState;

	beforeEach( () => {
		mockContext = null;
		( getContext as jest.Mock ).mockClear();
		( findCartLine as jest.Mock ).mockReset().mockReturnValue( undefined );

		mockState = {
			products: {},
			productVariations: {},
			template: { productId: 0, variation: [] },
			productScopes: {},
		};

		// `scope.ts` never calls `store()` itself (see `bindState`'s
		// docblock): the real module binds the store's own returned state
		// reference once, from `index.ts`. The test does the same, binding
		// this suite's own mock state directly.
		jest.isolateModules( () => {
			const scopeModule =
				require( '../scope' ) as typeof import('../scope');
			scopeModule.bindState( mockState );
			scopeState = scopeModule.scopeState;
		} );
	} );

	describe( 'identity: the full write scenario', () => {
		it( 'writing variation then draftCartItem.quantity leaves the record, the context and productVariation consistent', () => {
			mockContext = { productId: 100, scopeName: 's' };
			mockState.products[ 100 ] = mockProduct( {
				id: 100,
				type: 'variable',
				variations: [
					{
						id: 501,
						attributes: [
							{ name: 'Color', value: 'Blue' },
							{ name: 'Size', value: 'Medium' },
						],
					},
				],
			} );
			mockState.productVariations[ 501 ] = mockProduct( {
				id: 501,
				name: 'Blue Medium',
			} );

			scopeState.productScope.variation = [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
				{ attribute: 'attribute_pa_size', value: 'Medium' },
			];
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 2;

			expect( mockState.productScopes.s.draftCartItem ).toEqual( {
				id: 100,
				variation: [
					{ attribute: 'attribute_pa_color', value: 'Blue' },
					{ attribute: 'attribute_pa_size', value: 'Medium' },
				],
				quantity: 2,
			} );
			expect( mockContext.variation ).toEqual( [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
				{ attribute: 'attribute_pa_size', value: 'Medium' },
			] );
			expect( scopeState.productScope.productVariation ).toBe(
				mockState.productVariations[ 501 ]
			);
		} );
	} );

	describe( 'record sharing', () => {
		it( 'shares one record between two scopes declaring the same scopeName', () => {
			mockContext = { productId: 10, scopeName: 'shared' };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 3;

			mockContext = { productId: 10, scopeName: 'shared' };
			expect( scopeState.productScope.draftCartItem?.quantity ).toBe( 3 );
		} );

		it( 'shares the _default record between two scopes declaring no scopeName', () => {
			mockContext = { productId: 10 };
			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.quantity = 5;

			mockContext = { productId: 20 };
			expect( scopeState.productScope.draftCartItem?.quantity ).toBe( 5 );
		} );
	} );

	describe( 'falling back to state.template', () => {
		it( 'resolves productId and variation from state.template when the scope declares neither', () => {
			mockState.template = {
				productId: 77,
				variation: [ { attribute: 'a', value: 'v' } ],
			};
			mockContext = {};

			expect( scopeState.productScope.productId ).toBe( 77 );
			expect( scopeState.productScope.variation ).toEqual( [
				{ attribute: 'a', value: 'v' },
			] );
		} );

		it( 'resolves productId 0 and an empty variation, without throwing, when the server never seeded template', () => {
			// Every page other than the single-product template reaches
			// this store with no `template` seeded at all.
			mockState.template = undefined;
			mockContext = {};

			expect( () => scopeState.productScope.productId ).not.toThrow();
			expect( scopeState.productScope.productId ).toBe( 0 );
			expect( scopeState.productScope.variation ).toEqual( [] );
		} );

		it( 'resolves findProductScope to productId 0 and an empty variation when the ref and template are both empty', () => {
			mockState.template = undefined;

			const envelope = scopeState.findProductScope( {} );

			expect( () => envelope.productId ).not.toThrow();
			expect( envelope.productId ).toBe( 0 );
			expect( envelope.variation ).toEqual( [] );
		} );
	} );

	describe( 'draftCartItem defaults', () => {
		it( 'reads quantity 1, and the scope’s id/variation, before anything is set', () => {
			mockContext = {
				productId: 42,
				variation: [
					{ attribute: 'attribute_pa_color', value: 'Red' },
				],
				scopeName: 'defaults',
			};

			const draft = scopeState.productScope.draftCartItem;

			expect( draft?.quantity ).toBe( 1 );
			expect( draft?.id ).toBe( 42 );
			expect( draft?.variation ).toEqual( [
				{ attribute: 'attribute_pa_color', value: 'Red' },
			] );
		} );
	} );

	describe( 'draftCartItem writes', () => {
		it( 'writes an arbitrary extension prop to the record only, leaving the context untouched', () => {
			mockContext = { productId: 5, scopeName: 'ext' };

			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.giftMessage = 'Hi!';

			expect(
				mockState.productScopes.ext.draftCartItem?.giftMessage
			).toBe( 'Hi!' );
			expect( 'giftMessage' in mockContext ).toBe( false );
		} );

		it( 'writing draftCartItem.id updates both the record and the context', () => {
			mockContext = { productId: 5, scopeName: 'idw' };

			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.id = 6;

			expect( mockState.productScopes.idw.draftCartItem?.id ).toBe( 6 );
			expect( mockContext.productId ).toBe( 6 );
		} );

		it( 'writing draftCartItem.variation updates both the record and the context', () => {
			mockContext = { productId: 5, scopeName: 'varw' };
			const attrs = [ { attribute: 'a', value: 'v' } ];

			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			scopeState.productScope.draftCartItem!.variation = attrs;

			expect(
				mockState.productScopes.varw.draftCartItem?.variation
			).toEqual( attrs );
			expect( mockContext.variation ).toEqual( attrs );
		} );
	} );

	describe( 'cartItem', () => {
		it( 'matches a selected variation by its own id, not the parent’s', () => {
			mockState.products[ 100 ] = mockProduct( {
				id: 100,
				type: 'variable',
				variations: [
					{
						id: 501,
						attributes: [ { name: 'Color', value: 'Blue' } ],
					},
				],
			} );
			mockState.productVariations[ 501 ] = mockProduct( { id: 501 } );
			const variation = [
				{ attribute: 'attribute_pa_color', value: 'Blue' },
			];
			const line = {
				id: 501,
				key: 'variation-line',
				type: 'variation',
				variation,
			} as FakeCartLine;
			( findCartLine as jest.Mock ).mockImplementation(
				fakeFindCartLine( [ line ] )
			);
			mockContext = { productId: 100, variation };

			expect( scopeState.productScope.cartItem ).toEqual( line );
		} );

		it( 'resolves to null when the cart does not hold the selected variation', () => {
			mockState.products[ 100 ] = mockProduct( {
				id: 100,
				type: 'variable',
				variations: [
					{
						id: 501,
						attributes: [ { name: 'Color', value: 'Blue' } ],
					},
				],
			} );
			mockState.productVariations[ 501 ] = mockProduct( { id: 501 } );
			( findCartLine as jest.Mock ).mockImplementation(
				fakeFindCartLine( [] )
			);
			mockContext = {
				productId: 100,
				variation: [
					{ attribute: 'attribute_pa_color', value: 'Blue' },
				],
			};

			expect( scopeState.productScope.cartItem ).toBeNull();
		} );

		it( 'resolves by cartItemKey regardless of productId and variation', () => {
			( findCartLine as jest.Mock ).mockImplementation(
				fakeFindCartLine( [ { id: 1, key: 'k1' } as FakeCartLine ] )
			);
			mockContext = { productId: 999, cartItemKey: 'k1' };

			expect( scopeState.productScope.cartItem ).toEqual( {
				id: 1,
				key: 'k1',
			} );
		} );

		it( 'matches a simple product by its own id', () => {
			mockState.products[ 7 ] = mockProduct( { id: 7 } );
			const line = { id: 7, key: 'l7' } as FakeCartLine;
			( findCartLine as jest.Mock ).mockImplementation(
				fakeFindCartLine( [ line ] )
			);
			mockContext = { productId: 7 };

			expect( scopeState.productScope.cartItem ).toEqual( line );
		} );

		it( 'matches directly when productId is itself a variation id', () => {
			mockState.productVariations[ 501 ] = mockProduct( { id: 501 } );
			const line = { id: 501, key: 'l501' } as FakeCartLine;
			( findCartLine as jest.Mock ).mockImplementation(
				fakeFindCartLine( [ line ] )
			);
			mockContext = { productId: 501 };

			expect( scopeState.productScope.cartItem ).toEqual( line );
		} );

		it( 'falls back to the raw productId when no product resolves', () => {
			const line = { id: 404, key: 'l404' } as FakeCartLine;
			( findCartLine as jest.Mock ).mockImplementation(
				fakeFindCartLine( [ line ] )
			);
			mockContext = { productId: 404 };

			expect( scopeState.productScope.cartItem ).toEqual( line );
		} );

		it( 'findProductScope resolves a grouped child’s cart line by the ref’s productId', () => {
			mockState.products[ 55 ] = mockProduct( { id: 55 } );
			const line = { id: 55, key: 'child' } as FakeCartLine;
			( findCartLine as jest.Mock ).mockImplementation(
				fakeFindCartLine( [ line ] )
			);

			const envelope = scopeState.findProductScope( { productId: 55 } );

			expect( envelope.cartItem ).toEqual( line );
		} );
	} );

	describe( 'no record is created by reading', () => {
		it( 'creates no record after reading every member of a scope with none', () => {
			mockContext = { productId: 9, scopeName: 'readonly-scope' };
			const scope = scopeState.productScope;

			void scope.scopeName;
			void scope.productId;
			void scope.variation;
			void scope.draftCartItem?.id;
			void scope.draftCartItem?.variation;
			void scope.draftCartItem?.quantity;
			void scope.baseProduct;
			void scope.productVariation;
			void scope.product;
			void scope.cartItem;

			expect(
				mockState.productScopes[ 'readonly-scope' ]
			).toBeUndefined();
		} );
	} );

	describe( 'server-seeded records', () => {
		it( 'honours a server-seeded record on the first read', () => {
			mockState.productScopes.seeded = {
				draftCartItem: { id: 321, variation: [], quantity: 4 },
			};
			mockContext = { productId: 1, scopeName: 'seeded' };

			expect( scopeState.productScope.productId ).toBe( 321 );
			expect( scopeState.productScope.draftCartItem?.id ).toBe( 321 );
		} );
	} );

	describe( 'findProductScope', () => {
		it( 'resolves baseProduct, productVariation and product from the ref regardless of records', () => {
			const product = mockProduct( { id: 1 } );
			mockState.products[ 1 ] = product;
			mockState.productScopes.unrelated = {
				draftCartItem: { id: 999 },
			};

			const envelope = scopeState.findProductScope( { productId: 1 } );

			expect( envelope.baseProduct ).toBe( product );
			expect( envelope.product ).toBe( product );
		} );

		it( 'pairs cartItem via the ref, independent of any record', () => {
			( findCartLine as jest.Mock ).mockImplementation(
				fakeFindCartLine( [ { id: 1, key: 'k1' } as FakeCartLine ] )
			);

			const envelope = scopeState.findProductScope( { productId: 1 } );

			expect( envelope.cartItem ).toEqual( { id: 1, key: 'k1' } );
		} );

		it( 'yields no draft when several unnamed records match the same product id', () => {
			mockState.productScopes.a = { draftCartItem: { id: 5 } };
			mockState.productScopes.b = { draftCartItem: { id: 5 } };

			const envelope = scopeState.findProductScope( { productId: 5 } );

			expect( envelope.draftCartItem ).toBeUndefined();
		} );

		it( 'resolves the draft when exactly one unnamed record matches', () => {
			mockState.productScopes.only = {
				draftCartItem: { id: 7, quantity: 3 },
			};

			const envelope = scopeState.findProductScope( { productId: 7 } );

			expect( envelope.draftCartItem?.quantity ).toBe( 3 );
		} );

		it( 'yields no draft when zero unnamed records match', () => {
			const envelope = scopeState.findProductScope( { productId: 123 } );

			expect( envelope.draftCartItem ).toBeUndefined();
		} );

		describe( 'with a scopeName', () => {
			it( 'addresses that scope directly even with no record, without creating one', () => {
				const envelope = scopeState.findProductScope( {
					productId: 8,
					scopeName: 'child',
				} );

				expect( envelope.draftCartItem?.id ).toBe( 8 );
				expect( mockState.productScopes.child ).toBeUndefined();
			} );

			it( 'writing through it creates the record under that name', () => {
				const envelope = scopeState.findProductScope( {
					productId: 8,
					scopeName: 'child',
				} );

				// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
				envelope.draftCartItem!.quantity = 2;

				expect( mockState.productScopes.child.draftCartItem ).toEqual( {
					id: 8,
					variation: [],
					quantity: 2,
				} );
			} );

			it( 'addresses the existing record when one exists, ignoring the ref’s own productId', () => {
				mockState.productScopes.child2 = {
					draftCartItem: { id: 8, quantity: 9 },
				};

				const envelope = scopeState.findProductScope( {
					productId: 999,
					scopeName: 'child2',
				} );

				expect( envelope.draftCartItem?.quantity ).toBe( 9 );
				expect( envelope.draftCartItem?.id ).toBe( 8 );
			} );
		} );
	} );

	describe( 'read-only members reject writes', () => {
		it( 'throws when writing to baseProduct, productVariation, product or cartItem', () => {
			mockContext = { productId: 1, scopeName: 'ro' };
			const scope = scopeState.productScope;

			expect( () => {
				// @ts-expect-error -- intentionally invalid write for the test.
				scope.baseProduct = null;
			} ).toThrow();
			expect( () => {
				// @ts-expect-error -- intentionally invalid write for the test.
				scope.productVariation = null;
			} ).toThrow();
			expect( () => {
				// @ts-expect-error -- intentionally invalid write for the test.
				scope.product = null;
			} ).toThrow();
			expect( () => {
				// @ts-expect-error -- intentionally invalid write for the test.
				scope.cartItem = null;
			} ).toThrow();
		} );

		it( 'throws when writing to scopeName, and neither reading nor writing it creates a record', () => {
			mockContext = { productId: 1, scopeName: 'ro-name' };
			const scope = scopeState.productScope;

			void scope.scopeName;
			expect( () => {
				// @ts-expect-error -- intentionally invalid write for the test.
				scope.scopeName = 'other';
			} ).toThrow();

			expect( mockState.productScopes[ 'ro-name' ] ).toBeUndefined();
		} );
	} );

	describe( 'scopeName', () => {
		it( 'resolves from the declared context, defaulting to _default when none is declared', () => {
			mockContext = { productId: 1, scopeName: 's' };
			expect( scopeState.productScope.scopeName ).toBe( 's' );

			mockContext = { productId: 1 };
			expect( scopeState.productScope.scopeName ).toBe( '_default' );
		} );

		it( 'findProductScope resolves scopeName from a named ref whether or not a record exists there', () => {
			expect(
				scopeState.findProductScope( { productId: 1, scopeName: 's' } )
					.scopeName
			).toBe( 's' );

			mockState.productScopes.s = { draftCartItem: { id: 1 } };
			expect(
				scopeState.findProductScope( { productId: 1, scopeName: 's' } )
					.scopeName
			).toBe( 's' );
		} );

		it( 'findProductScope resolves an unnamed ref to the single matching record’s name, or null when none or several match', () => {
			expect(
				scopeState.findProductScope( { productId: 5 } ).scopeName
			).toBeNull();

			mockState.productScopes.only = { draftCartItem: { id: 5 } };
			expect(
				scopeState.findProductScope( { productId: 5 } ).scopeName
			).toBe( 'only' );

			mockState.productScopes.another = { draftCartItem: { id: 5 } };
			expect(
				scopeState.findProductScope( { productId: 5 } ).scopeName
			).toBeNull();
		} );

		it( 'names the same record draftCartItem is addressed at, for an unnamed ref matching one record', () => {
			mockState.productScopes.only = {
				draftCartItem: { id: 7, quantity: 3 },
			};

			const envelope = scopeState.findProductScope( { productId: 7 } );

			expect( envelope.scopeName ).toBe( 'only' );
			expect( envelope.draftCartItem?.quantity ).toBe( 3 );
		} );
	} );

	describe( 'a product that was never loaded', () => {
		it( 'resolves baseProduct, productVariation and product to null instead of throwing', () => {
			mockContext = { productId: 55, scopeName: 'unloaded' };
			const scope = scopeState.productScope;

			expect( () => scope.baseProduct ).not.toThrow();
			expect( scope.baseProduct ).toBeNull();
			expect( scope.productVariation ).toBeNull();
			expect( scope.product ).toBeNull();
		} );
	} );

	describe( 'productScope reads nothing reactive', () => {
		it( 'does not call getContext while building the envelope, only once a member is accessed', () => {
			mockContext = { productId: 1, scopeName: 'a6' };
			( getContext as jest.Mock ).mockClear();

			const scope = scopeState.productScope;
			expect( getContext ).not.toHaveBeenCalled();

			void scope.productId;
			expect( getContext ).toHaveBeenCalled();
		} );

		it( 'reading a scope with no record repeatedly never throws and never creates a record', () => {
			mockContext = { productId: 2, scopeName: 'no-record' };

			for ( let i = 0; i < 5; i++ ) {
				expect(
					() => scopeState.productScope.draftCartItem?.quantity
				).not.toThrow();
			}

			expect( mockState.productScopes[ 'no-record' ] ).toBeUndefined();
		} );
	} );

	// Exercises `findProductScope`'s `baseProduct`, `productVariation` and
	// `product` resolution against a matching candidate. One case worth
	// calling out: when selected attributes match a candidate that is not
	// yet populated in `productVariations`, `product` falls back to
	// `baseProduct` (following the rule that `product` is `productVariation`
	// when one resolved, otherwise `baseProduct`), while `productVariation`
	// stays `null` either way.
	describe( 'resolving baseProduct, productVariation and product', () => {
		describe( 'baseProduct', () => {
			it( 'returns the product regardless of a selected variation', () => {
				mockState.products[ 42 ] = mockProduct( { id: 42 } );
				mockContext = {
					productId: 42,
					variation: [ { attribute: 'a', value: 'v' } ],
				};

				expect( scopeState.productScope.baseProduct ).toBe(
					mockState.products[ 42 ]
				);
			} );

			it( 'returns null when the product is not in the store', () => {
				mockContext = { productId: 999 };

				expect( scopeState.productScope.baseProduct ).toBeNull();
			} );

			it( 'returns null when productId is 0', () => {
				mockContext = null;

				expect( scopeState.productScope.baseProduct ).toBeNull();
			} );

			it( 'reads productId from the declared context', () => {
				mockState.products[ 42 ] = mockProduct( { id: 42 } );
				mockContext = { productId: 42 };

				expect( scopeState.productScope.baseProduct ).toBe(
					mockState.products[ 42 ]
				);
			} );
		} );

		describe( 'productVariation', () => {
			it( 'returns null when nothing is selected on a simple product', () => {
				mockState.products[ 42 ] = mockProduct( { id: 42 } );
				mockContext = { productId: 42 };

				expect( scopeState.productScope.productVariation ).toBeNull();
			} );

			it( 'returns null when nothing is selected on a variable product', () => {
				mockState.products[ 10 ] = mockProduct( {
					id: 10,
					type: 'variable',
				} );
				mockContext = { productId: 10 };

				expect( scopeState.productScope.productVariation ).toBeNull();
			} );

			it( 'returns the matching populated variation when selected', () => {
				mockState.products[ 42 ] = mockProduct( {
					id: 42,
					type: 'variable',
					variations: [
						{
							id: 99,
							attributes: [ { name: 'Color', value: 'Blue' } ],
						},
					],
				} );
				mockState.productVariations[ 99 ] = mockProduct( { id: 99 } );
				mockContext = {
					productId: 42,
					variation: [ { attribute: 'Color', value: 'Blue' } ],
				};

				expect( scopeState.productScope.productVariation ).toBe(
					mockState.productVariations[ 99 ]
				);
			} );

			it( 'returns null when the selection does not match any variation', () => {
				mockState.products[ 42 ] = mockProduct( {
					id: 42,
					type: 'variable',
					variations: [
						{
							id: 99,
							attributes: [ { name: 'Color', value: 'Blue' } ],
						},
					],
				} );
				mockContext = {
					productId: 42,
					variation: [ { attribute: 'Color', value: 'Green' } ],
				};

				expect( scopeState.productScope.productVariation ).toBeNull();
			} );
		} );

		describe( 'product', () => {
			it( 'returns null when the product is not in the store (findProduct)', () => {
				const envelope = scopeState.findProductScope( {
					productId: 999,
				} );

				expect( envelope.product ).toBeNull();
			} );

			it( 'returns the product itself for a simple product (findProduct)', () => {
				const simpleProduct = mockProduct( { id: 1 } );
				mockState.products[ 1 ] = simpleProduct;

				const envelope = scopeState.findProductScope( {
					productId: 1,
				} );

				expect( envelope.product ).toBe( simpleProduct );
			} );

			it( 'returns the matched, populated variation (findProduct)', () => {
				const variableProduct = mockProduct( {
					id: 1,
					type: 'variable',
					variations: [
						{
							id: 10,
							attributes: [ { name: 'Color', value: 'red' } ],
						},
					],
				} );
				const populatedVariation = mockProduct( {
					id: 10,
					name: 'Red Variation',
				} );
				mockState.products[ 1 ] = variableProduct;
				mockState.productVariations[ 10 ] = populatedVariation;

				const envelope = scopeState.findProductScope( {
					productId: 1,
					variation: [ { attribute: 'Color', value: 'red' } ],
				} );

				expect( envelope.product ).toBe( populatedVariation );
			} );

			it( 'productVariation stays null when attributes match but the variation is not populated (findProduct)', () => {
				const variableProduct = mockProduct( {
					id: 1,
					type: 'variable',
					variations: [
						{
							id: 10,
							attributes: [ { name: 'Color', value: 'red' } ],
						},
					],
				} );
				mockState.products[ 1 ] = variableProduct;
				// productVariations intentionally empty.

				const envelope = scopeState.findProductScope( {
					productId: 1,
					variation: [ { attribute: 'Color', value: 'red' } ],
				} );

				expect( envelope.productVariation ).toBeNull();
			} );

			it( 'returns the parent product when variable and nothing is selected (findProduct)', () => {
				const variableProduct = mockProduct( {
					id: 1,
					type: 'variable',
					variations: [
						{
							id: 10,
							attributes: [ { name: 'Color', value: 'red' } ],
						},
					],
				} );
				mockState.products[ 1 ] = variableProduct;

				expect(
					scopeState.findProductScope( { productId: 1 } ).product
				).toBe( variableProduct );
				expect(
					scopeState.findProductScope( {
						productId: 1,
						variation: [],
					} ).product
				).toBe( variableProduct );
			} );

			it( 'productVariation stays null when attributes do not match any variation (findProduct)', () => {
				const variableProduct = mockProduct( {
					id: 1,
					type: 'variable',
					variations: [
						{
							id: 10,
							attributes: [ { name: 'Color', value: 'red' } ],
						},
					],
				} );
				mockState.products[ 1 ] = variableProduct;
				mockState.productVariations[ 10 ] = mockProduct( { id: 10 } );

				const envelope = scopeState.findProductScope( {
					productId: 1,
					variation: [ { attribute: 'Color', value: 'blue' } ],
				} );

				expect( envelope.productVariation ).toBeNull();
			} );

			it( 'resolves a variation given directly, ignoring the variation selection (findProduct)', () => {
				const variation = mockProduct( {
					id: 50,
					name: 'Direct Variation',
				} );
				mockState.productVariations[ 50 ] = variation;

				const envelope = scopeState.findProductScope( {
					productId: 50,
					variation: [ { attribute: 'Color', value: 'blue' } ],
				} );

				expect( envelope.product ).toBe( variation );
			} );

			it( 'prefers the variation lookup over the product lookup when the id exists in both (findProduct)', () => {
				const product = mockProduct( { id: 50, name: 'Product 50' } );
				const variation = mockProduct( {
					id: 50,
					name: 'Variation 50',
				} );
				mockState.products[ 50 ] = product;
				mockState.productVariations[ 50 ] = variation;

				const envelope = scopeState.findProductScope( {
					productId: 50,
				} );

				expect( envelope.product ).toBe( variation );
			} );

			describe( 'attribute matching (variable products)', () => {
				it( 'matches with attribute prefixes in the selection', () => {
					const populatedVariation301 = mockProduct( {
						id: 301,
						name: 'Blue Small',
					} );
					mockState.products[ 3 ] = mockProduct( {
						id: 3,
						type: 'variable',
						variations: [
							{
								id: 301,
								attributes: [
									{ name: 'Color', value: 'Blue' },
									{ name: 'Size', value: 'Small' },
								],
							},
							{
								id: 302,
								attributes: [
									{ name: 'Color', value: 'Blue' },
									{ name: 'Size', value: 'Large' },
								],
							},
						],
					} );
					mockState.productVariations[ 301 ] = populatedVariation301;
					mockState.productVariations[ 302 ] = mockProduct( {
						id: 302,
						name: 'Blue Large',
					} );

					const envelope = scopeState.findProductScope( {
						productId: 3,
						variation: [
							{ attribute: 'attribute_pa_color', value: 'Blue' },
							{ attribute: 'attribute_pa_size', value: 'Small' },
						],
					} );

					expect( envelope.product ).toBe( populatedVariation301 );
				} );

				it( 'matches multi-word attribute names given as hyphenated slugs', () => {
					const populatedVariation = mockProduct( {
						id: 301,
						name: 'Blue 42',
					} );
					mockState.products[ 3 ] = mockProduct( {
						id: 3,
						type: 'variable',
						variations: [
							{
								id: 301,
								attributes: [
									{ name: 'Color', value: 'Blue' },
									{ name: 'numeric size', value: '42' },
								],
							},
						],
					} );
					mockState.productVariations[ 301 ] = populatedVariation;

					const envelope = scopeState.findProductScope( {
						productId: 3,
						variation: [
							{ attribute: 'attribute_pa_color', value: 'Blue' },
							{
								attribute: 'attribute_pa_numeric-size',
								value: '42',
							},
						],
					} );

					expect( envelope.product ).toBe( populatedVariation );
				} );
			} );

			describe( '"Any" attribute handling', () => {
				it( 'matches a variation with an Any attribute when a value is selected', () => {
					const populatedVariation = mockProduct( {
						id: 201,
						name: 'Any Color Small',
					} );
					mockState.products[ 2 ] = mockProduct( {
						id: 2,
						type: 'variable',
						variations: [
							{
								id: 201,
								attributes: [
									{ name: 'Color', value: null },
									{ name: 'Size', value: 'Small' },
								],
							},
							{
								id: 202,
								attributes: [
									{ name: 'Color', value: 'Blue' },
									{ name: 'Size', value: null },
								],
							},
						],
					} as unknown as Partial< ProductResponseItem > );
					mockState.productVariations[ 201 ] = populatedVariation;

					const envelope = scopeState.findProductScope( {
						productId: 2,
						variation: [
							{ attribute: 'Color', value: 'Red' },
							{ attribute: 'Size', value: 'Small' },
						],
					} );

					expect( envelope.product ).toBe( populatedVariation );
				} );

				it( 'does not match an Any attribute when the selected value is null', () => {
					mockState.products[ 2 ] = mockProduct( {
						id: 2,
						type: 'variable',
						variations: [
							{
								id: 201,
								attributes: [
									{ name: 'Color', value: null },
									{ name: 'Size', value: 'Small' },
								],
							},
						],
					} as unknown as Partial< ProductResponseItem > );

					const envelope = scopeState.findProductScope( {
						productId: 2,
						variation: [
							{
								attribute: 'Color',
								value: null as unknown as string,
							},
							{ attribute: 'Size', value: 'Small' },
						],
					} );

					expect( envelope.productVariation ).toBeNull();
				} );

				it( 'does not match an Any attribute when it is not selected at all', () => {
					mockState.products[ 2 ] = mockProduct( {
						id: 2,
						type: 'variable',
						variations: [
							{
								id: 201,
								attributes: [
									{ name: 'Color', value: null },
									{ name: 'Size', value: 'Small' },
								],
							},
						],
					} as unknown as Partial< ProductResponseItem > );

					const envelope = scopeState.findProductScope( {
						productId: 2,
						variation: [ { attribute: 'Size', value: 'Small' } ],
					} );

					expect( envelope.productVariation ).toBeNull();
				} );
			} );
		} );
	} );

	// The suites above bind a plain object as the store's state, which has
	// no traps and no signals: a raw write and a write through the state
	// reference are indistinguishable there, so they cannot see a scope's
	// first write land on the wrong one. `makeReactive` reproduces the one
	// real-proxy behaviour this defect depends on — reading a key that does
	// not exist yet fixes what every later read of that key returns, even
	// after a raw write past the proxy — so these tests fail if
	// `ensureRecord`/`writeIdentity` create the record before resolving its
	// identity snapshot, rather than resolving it first.
	describe( 'a scope record reaching a reactive state (first-write coverage)', () => {
		let reactiveScopeState: ScopeState;
		let productScopes: ProductScopesState;

		beforeEach( () => {
			mockContext = null;

			const reactiveProductScopes = makeReactive(
				{} as ProductScopesState
			);
			const reactiveMockState: MockState = {
				products: {},
				productVariations: {},
				template: { productId: 0, variation: [] },
				productScopes: reactiveProductScopes,
			};
			productScopes = reactiveProductScopes;

			jest.isolateModules( () => {
				const scopeModule =
					require( '../scope' ) as typeof import('../scope');
				scopeModule.bindState( reactiveMockState );
				reactiveScopeState = scopeModule.scopeState;
			} );
		} );

		it( 'leaves the first draftCartItem.quantity write on a fresh scope readable through the state reference, and a second write replaces it', () => {
			mockContext = { productId: 10, scopeName: 'fresh' };

			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			reactiveScopeState.productScope.draftCartItem!.quantity = 2;

			expect( productScopes.fresh.draftCartItem?.quantity ).toBe( 2 );
			expect( productScopes.fresh.draftCartItem?.id ).toBe( 10 );

			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			reactiveScopeState.productScope.draftCartItem!.quantity = 5;

			expect( productScopes.fresh.draftCartItem?.quantity ).toBe( 5 );
			expect( productScopes.fresh.draftCartItem?.id ).toBe( 10 );
		} );

		it( 'leaves the first draftCartItem.variation write on a fresh scope with no locator readable through the variation accessor', () => {
			mockContext = null;
			const attrs = [ { attribute: 'a', value: 'v' } ];

			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			reactiveScopeState.productScope.draftCartItem!.variation = attrs;

			expect( reactiveScopeState.productScope.variation ).toEqual(
				attrs
			);
		} );

		it( 'still updates the locator when writing draftCartItem.variation on a fresh scope that declares one', () => {
			mockContext = { productId: 30, scopeName: 'withlocator' };
			const attrs = [ { attribute: 'a', value: 'v' } ];

			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			reactiveScopeState.productScope.draftCartItem!.variation = attrs;

			expect( mockContext.variation ).toEqual( attrs );
		} );

		it( 'a record created by writing only variation still carries the id resolved before the write', () => {
			mockContext = { productId: 40, scopeName: 'idcarry' };
			const attrs = [ { attribute: 'a', value: 'v' } ];

			// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
			reactiveScopeState.productScope.draftCartItem!.variation = attrs;

			expect( productScopes.idcarry.draftCartItem?.id ).toBe( 40 );
			expect( productScopes.idcarry.draftCartItem?.variation ).toEqual(
				attrs
			);
		} );
	} );
} );
