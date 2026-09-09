/**
 * Internal dependencies
 */
import { schemaParser } from '../index';

describe( 'Date schema comparisons', () => {
	it.each( [
		[ 'formatMinimum', '2026-05-01', false ],
		[ 'formatMinimum', '2026-05-02', true ],
		[ 'formatMinimum', '2026-05-03', true ],
		[ 'formatMaximum', '2026-05-01', true ],
		[ 'formatMaximum', '2026-05-02', true ],
		[ 'formatMaximum', '2026-05-03', false ],
		[ 'formatExclusiveMinimum', '2026-05-01', false ],
		[ 'formatExclusiveMinimum', '2026-05-02', false ],
		[ 'formatExclusiveMinimum', '2026-05-03', true ],
		[ 'formatExclusiveMaximum', '2026-05-01', true ],
		[ 'formatExclusiveMaximum', '2026-05-02', false ],
		[ 'formatExclusiveMaximum', '2026-05-03', false ],
		[ 'formatMaximum', '2026-02-30', false ],
		[ 'formatMaximum', '2026--02--01', false ],
		[ 'formatMaximum', '2026/02/01', false ],
	] )( '%s validates %s as %s', ( keyword, value, expected ) => {
		for ( const limit of [
			'2026-05-02',
			{ $data: '1/hotel~1reference' },
			{ $data: '/hotel~1reference' },
		] ) {
			const validate = schemaParser.compile( {
				type: 'object',
				properties: {
					date: {
						type: 'string',
						format: 'date',
						[ keyword ]: limit,
					},
				},
			} );
			expect(
				validate( { date: value, 'hotel/reference': '2026-05-02' } )
			).toBe( expected );
		}
	} );
} );
