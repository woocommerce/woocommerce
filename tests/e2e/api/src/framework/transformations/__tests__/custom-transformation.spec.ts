import { CustomTransformation } from '../custom-transformation';

describe( 'CustomTransformation', () => {
	it( 'should do nothing without hooks', () => {
		const transformation = new CustomTransformation( 0, null, null );

		const expected = { test: 'Test' };

		expect( transformation.toModel( expected ) ).toMatchObject( expected );
		expect( transformation.fromModel( expected ) ).toMatchObject( expected );
	} );

	it( 'should execute hooks', () => {
		const toHook = jest.fn();
		toHook.mockReturnValue( { toModel: 'Test' } );
		const fromHook = jest.fn();
		fromHook.mockReturnValue( { fromModel: 'Test' } );

		const transformation = new CustomTransformation( 0, toHook, fromHook );

		expect( transformation.toModel( { test: 'Test' } ) ).toMatchObject( { toModel: 'Test' } );
		expect( toHook ).toHaveBeenCalledWith( { test: 'Test' } );
		expect( transformation.fromModel( { test: 'Test' } ) ).toMatchObject( { fromModel: 'Test' } );
		expect( fromHook ).toHaveBeenCalledWith( { test: 'Test' } );
	} );
} );
