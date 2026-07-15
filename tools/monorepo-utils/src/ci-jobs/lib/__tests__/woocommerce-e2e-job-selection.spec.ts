/**
 * External dependencies
 */
import path from 'node:path';

/**
 * Internal dependencies
 */
import { JobType, parseCIConfig, TestJobConfig } from '../config';
import { loadPackage } from '../package-file';

const packageConfig = parseCIConfig(
	loadPackage(
		path.resolve(
			__dirname,
			'../../../../../../plugins/woocommerce/package.json'
		)
	)
);
const pullRequestE2EJobs = packageConfig.jobs.filter(
	( job ): job is TestJobConfig =>
		job.type === JobType.Test &&
		job.testType === 'e2e' &&
		job.events.includes( 'pull_request' )
);
const coreJobCandidates = pullRequestE2EJobs.filter( ( job ) =>
	job.name.startsWith( 'Core e2e tests' )
);
const blocksJobs = pullRequestE2EJobs.filter( ( job ) =>
	job.name.startsWith( 'Blocks e2e tests' )
);

function matchingJobNames(
	jobs: TestJobConfig[],
	changedPath: string
): string[] {
	return jobs
		.filter( ( job ) =>
			job.changes.some( ( change ) => {
				change.lastIndex = 0;
				return change.test( changedPath );
			} )
		)
		.map( ( job ) => job.name );
}

const specializedCoreJobs = coreJobCandidates.filter(
	( job ) =>
		matchingJobNames( [ job ], 'tests/e2e/tests/order/order-edit.spec.ts' )
			.length === 0
);
const coreJobs = coreJobCandidates.filter(
	( job ) => ! specializedCoreJobs.includes( job )
);

describe( 'WooCommerce E2E job selection', () => {
	it( 'discovers Core and Blocks pull-request E2E jobs', () => {
		expect( coreJobs ).not.toHaveLength( 0 );
		expect( specializedCoreJobs ).not.toHaveLength( 0 );
		expect( blocksJobs ).not.toHaveLength( 0 );
	} );

	it.each( specializedCoreJobs )(
		'keeps specialized Core job $name outside the generic family',
		( job ) => {
			expect( job.command ).toBe( 'test:e2e:paypal-standard' );
			expect(
				matchingJobNames(
					[ job ],
					'tests/e2e/tests/paypal/paypal.spec.ts'
				)
			).toEqual( [ job.name ] );

			for ( const changedPath of [
				'tests/e2e/tests/order/order-edit.spec.ts',
				'tests/e2e/playwright.config.ts',
				'tests/e2e/tests/blocks/cart/cart.page.ts',
			] ) {
				expect( matchingJobNames( [ job ], changedPath ) ).toEqual(
					[]
				);
			}
		}
	);

	it.each( [
		'tests/e2e/tests/blocks/cart/cart.block_theme.spec.ts',
		'tests/e2e/tests/blocks/cart/cart.page.ts',
	] )( 'selects only Blocks jobs for %s', ( changedPath ) => {
		expect( matchingJobNames( blocksJobs, changedPath ) ).toEqual(
			blocksJobs.map( ( job ) => job.name )
		);
		expect( matchingJobNames( coreJobs, changedPath ) ).toEqual( [] );
	} );

	it.each( [
		'tests/e2e/tests/order/order-edit.spec.ts',
		'tests/e2e/tests/root.spec.ts',
	] )( 'selects only Core jobs for %s', ( changedPath ) => {
		expect( matchingJobNames( coreJobs, changedPath ) ).toEqual(
			coreJobs.map( ( job ) => job.name )
		);
		expect( matchingJobNames( blocksJobs, changedPath ) ).toEqual( [] );
	} );

	it.each( [
		'tests/e2e/playwright.config.ts',
		'tests/e2e/fixtures/blocks-setup.ts',
		'tests/e2e/utils/blocks/constants.ts',
		'tests/e2e/bin/blocks/setup.sh',
	] )( 'selects every E2E job for shared path %s', ( changedPath ) => {
		expect( matchingJobNames( coreJobs, changedPath ) ).toEqual(
			coreJobs.map( ( job ) => job.name )
		);
		expect( matchingJobNames( blocksJobs, changedPath ) ).toEqual(
			blocksJobs.map( ( job ) => job.name )
		);
	} );
} );
