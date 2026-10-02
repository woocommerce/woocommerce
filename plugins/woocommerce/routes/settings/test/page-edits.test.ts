/**
 * Internal dependencies
 */
import { pickEdits } from '../page-edits';

describe( 'pickEdits', () => {
	it( 'keeps only the edits to the given fields', () => {
		expect(
			pickEdits( { title: 'New', email: 'a@example.com', mode: 'test' }, [
				'title',
				'mode',
				'enabled',
			] )
		).toEqual( { title: 'New', mode: 'test' } );
	} );

	it( 'returns nothing when no edits are on the page', () => {
		expect( pickEdits( { email: 'a@example.com' }, [ 'title' ] ) ).toEqual(
			{}
		);
	} );
} );
