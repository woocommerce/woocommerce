/**
 * External dependencies
 */
import path from 'node:path';
import { makeRe } from 'minimatch';

/**
 * Internal dependencies
 */
import { JobType, parseCIConfig, TestJobConfig } from '../config';
import { loadPackage } from '../package-file';

const EXPECTED_GENERIC_CORE_JOB_NAMES = [
	'Core e2e tests - parallel',
	'Core e2e tests - serial',
	'Core e2e tests - Gutenberg stable',
	'Core e2e tests - (HPOS:off)',
	'Core e2e tests - PHP 8.5 - serial',
	'Core e2e tests - PHP 8.5 - parallel',
	'Core e2e tests - WP L-1 - serial',
	'Core e2e tests - WP L-1 - parallel',
	'Core e2e tests - WP pre-release - serial',
	'Core e2e tests - WP pre-release - parallel',
];
const EXPECTED_SPECIALIZED_CORE_JOB_NAMES = [
	'Core e2e tests - PayPal Standard',
];
const EXPECTED_BLOCKS_JOB_NAMES = [
	'Blocks e2e tests',
	'Blocks e2e tests - WP pre-release',
	'Blocks e2e tests - WP latest-1',
];

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

function sortedJobNames( jobs: TestJobConfig[] ): string[] {
	return jobs.map( ( job ) => job.name ).sort();
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
	it( 'discovers every generic Core pull-request E2E job', () => {
		expect( sortedJobNames( coreJobs ) ).toEqual(
			[ ...EXPECTED_GENERIC_CORE_JOB_NAMES ].sort()
		);
	} );

	it( 'discovers every specialized Core pull-request E2E job', () => {
		expect( sortedJobNames( specializedCoreJobs ) ).toEqual(
			[ ...EXPECTED_SPECIALIZED_CORE_JOB_NAMES ].sort()
		);
	} );

	it( 'discovers every Blocks pull-request E2E job', () => {
		expect( sortedJobNames( blocksJobs ) ).toEqual(
			[ ...EXPECTED_BLOCKS_JOB_NAMES ].sort()
		);
	} );

	it.each( specializedCoreJobs )(
		'keeps specialized Core job $name outside the generic family',
		( job ) => {
			expect( job.command ).toBe( 'test:e2e:paypal-standard' );
			expect( job.changes ).toEqual( [
				makeRe( 'package.json' ),
				makeRe( 'includes/gateways/paypal/**/*.php' ),
				makeRe( 'src/Gateways/PayPal/**/*.php' ),
				makeRe( 'tests/e2e/tests/paypal/**' ),
			] );
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
