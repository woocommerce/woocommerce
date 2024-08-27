/**
 * Internal dependencies
 */
import { mergeLogData } from '../utils';
import { LogData } from '../types';

describe( 'mergeLogData', () => {
	it( 'should merge basic properties', () => {
		const target: LogData = {
			message: 'Target message',
			feature: 'target_feature',
			severity: 'info',
		};
		const source: Partial< LogData > = {
			message: 'Source message',
			severity: 'error',
		};
		const result = mergeLogData( target, source );
		expect( result ).toEqual( {
			message: 'Source message',
			feature: 'target_feature',
			severity: 'error',
		} );
	} );

	it( 'should merge extra properties', () => {
		const target: LogData = {
			message: 'Test',
			extra: { a: 1, b: 2 },
		};
		const source: Partial< LogData > = {
			extra: { b: 3, c: 4 },
		};
		const result = mergeLogData( target, source );
		expect( result.extra ).toEqual( { a: 1, b: 3, c: 4 } );
	} );

	it( 'should merge properties', () => {
		const target: LogData = {
			message: 'Test',
			properties: { x: 'a', y: 'b' },
		};
		const source: Partial< LogData > = {
			properties: { y: 'c', z: 'd' },
		};
		const result = mergeLogData( target, source );
		expect( result.properties ).toEqual( { x: 'a', y: 'c', z: 'd' } );
	} );

	it( 'should concatenate tags', () => {
		const target: LogData = {
			message: 'Test',
			tags: [ 'tag1', 'tag2' ],
		};
		const source: Partial< LogData > = {
			tags: [ 'tag3', 'tag4' ],
		};
		const result = mergeLogData( target, source );
		expect( result.tags ).toEqual( [ 'tag1', 'tag2', 'tag3', 'tag4' ] );
	} );

	it( 'should handle missing properties in source', () => {
		const target: LogData = {
			message: 'Target message',
			feature: 'target_feature',
			severity: 'info',
			extra: { a: 1 },
			properties: { x: 'a' },
			tags: [ 'tag1' ],
		};
		const source: Partial< LogData > = {
			message: 'Source message',
		};
		const result = mergeLogData( target, source );
		expect( result ).toEqual( {
			message: 'Source message',
			feature: 'target_feature',
			severity: 'info',
			extra: { a: 1 },
			properties: { x: 'a' },
			tags: [ 'tag1' ],
		} );
	} );

	it( 'should handle missing properties in target', () => {
		const target: LogData = {
			message: 'Target message',
		};
		const source: Partial< LogData > = {
			feature: 'source_feature',
			severity: 'error',
			extra: { b: 2 },
			properties: { y: 'b' },
			tags: [ 'tag2' ],
		};
		const result = mergeLogData( target, source );
		expect( result ).toEqual( {
			message: 'Target message',
			feature: 'source_feature',
			severity: 'error',
			extra: { b: 2 },
			properties: { y: 'b' },
			tags: [ 'tag2' ],
		} );
	} );

	it( 'should not modify the original target object', () => {
		const target: LogData = {
			message: 'Target message',
			extra: { a: 1 },
			tags: [ 'tag1' ],
		};
		const source: Partial< LogData > = {
			message: 'Source message',
			extra: { b: 2 },
			tags: [ 'tag2' ],
		};
		const result = mergeLogData( target, source );
		expect( target ).toEqual( {
			message: 'Target message',
			extra: { a: 1 },
			tags: [ 'tag1' ],
		} );
		expect( result ).not.toBe( target );
	} );
} );
