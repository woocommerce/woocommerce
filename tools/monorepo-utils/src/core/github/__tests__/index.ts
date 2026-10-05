import { expect, it, vi } from 'vitest';

/**
 * Internal dependencies
 */
import { getLatestGithubReleaseVersion } from '../repo';

vi.mock( '../api', () => {
	return ( ( mock ) => ( { default: mock, ...mock } ) )( {
		graphqlWithAuth: () =>
			vi.fn().mockResolvedValue( {
				repository: {
					releases: {
						nodes: [
							{
								tagName: 'nightly',
								isLatest: false,
							},
							{
								tagName: 'wc-beta-tester-99.99.0',
								isLatest: false,
							},
							{
								tagName: '1.0.0',
								isLatest: false,
							},
							{
								tagName: '1.1.0',
								isLatest: false,
							},
							{
								tagName: '1.2.0',
								isLatest: false,
							},
							{
								tagName: '2.0.0',
								isLatest: false,
							},
							{
								tagName: '2.0.1',
								isLatest: true,
							},
							{
								tagName: '1.0.1',
								isLatest: false,
							},
						],
					},
				},
			} ),
	} );
} );

it( 'should return the latest release version', async () => {
	expect(
		await getLatestGithubReleaseVersion( {
			owner: 'woocommerce',
			name: 'woocommerce',
		} )
	).toBe( '2.0.1' );
} );
