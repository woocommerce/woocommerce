/**
 * Internal dependencies
 */
import { createHierarchicalList } from '../create-hierarchical-list';
import type { FilterOptionItem } from '../../types';

const createTerm = (
	termId: number,
	label: string,
	parent = 0
): FilterOptionItem => ( {
	id: String( termId ),
	termId,
	label,
	value: label,
	selected: false,
	parent,
} );

describe( 'createHierarchicalList', () => {
	it( 'returns an empty list when no terms match', () => {
		expect( createHierarchicalList( [], 'name-asc' ) ).toEqual( [] );
	} );

	it( 'retains a matching child when its parent is absent', () => {
		const root = createTerm( 1, 'Clothing' );
		const orphan = createTerm( 3, 'Hoodies', 2 );
		const grandchild = createTerm( 4, 'Zip hoodies', 3 );

		expect(
			createHierarchicalList( [ grandchild, orphan, root ], 'name-asc' )
		).toEqual( [
			{ ...root, depth: 0 },
			{ ...orphan, depth: 0 },
			{ ...grandchild, depth: 1 },
		] );
	} );

	it( 'retains matching terms when every parent is absent', () => {
		const orphan = createTerm( 3, 'Hoodies', 2 );

		expect( createHierarchicalList( [ orphan ], 'name-asc' ) ).toEqual( [
			{ ...orphan, depth: 0 },
		] );
	} );

	it( 'sorts siblings while keeping descendants after their parent', () => {
		const root = createTerm( 1, 'Clothing' );
		const otherRoot = createTerm( 2, 'Accessories' );
		const child = createTerm( 3, 'Hoodies', 1 );
		const otherChild = createTerm( 4, 'T-shirts', 1 );
		const grandchild = createTerm( 5, 'Zip hoodies', 3 );

		expect(
			createHierarchicalList(
				[ otherChild, grandchild, root, child, otherRoot ],
				'name-desc'
			)
		).toEqual( [
			{ ...root, depth: 0 },
			{ ...otherChild, depth: 1 },
			{ ...child, depth: 1 },
			{ ...grandchild, depth: 2 },
			{ ...otherRoot, depth: 0 },
		] );
	} );
} );
