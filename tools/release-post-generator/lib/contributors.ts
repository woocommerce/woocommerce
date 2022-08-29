/**
 * External dependencies
 */
import { checkoutRef, sparseCheckoutRepo } from 'code-analyzer/src/git';
import { readFile } from 'fs/promises';
import { join } from 'path';
import semver from 'semver';

/**
 * Internal dependencies
 */
import { getContributors } from './github-api';

const OTHER_WATCHED_PACKAGES = [
	{
		displayName: 'WooCommerce Blocks',
		packagist: 'woocommerce/woocommerce-blocks',
		org: 'woocommerce',
		repo: 'woocommerce-blocks',
		versionPrefix: 'v',
	},
	{
		displayName: 'ActionScheduler',
		packagist: 'woocommerce/action-scheduler',
		org: 'woocommerce',
		repo: 'action-scheduler',
		versionPrefix: '',
	},
];

export const generateContributors = async (
	currentVersion: string,
	previousVersion: string
) => {
	const repoPath = await sparseCheckoutRepo(
		'https://github.com/woocommerce/woocommerce.git',
		'woocommerce',
		[ 'plugins/woocommerce/composer.json' ]
	);

	await checkoutRef( repoPath, currentVersion );

	const currentComposer = JSON.parse(
		await readFile(
			join( repoPath, 'plugins/woocommerce/composer.json' ),
			'utf-8'
		)
	);

	await checkoutRef( repoPath, previousVersion.toString() );

	const previousComposer = JSON.parse(
		await readFile(
			join( repoPath, 'plugins/woocommerce/composer.json' ),
			'utf-8'
		)
	);

	const currentRequire = currentComposer.require;
	const previousRequire = previousComposer.require;

	const coreContributors = await getContributors(
		'woocommerce',
		'woocommerce',
		previousVersion,
		currentVersion
	);

	const dependencyContributors: Record< string, unknown[] > = {};

	for ( const pkg of OTHER_WATCHED_PACKAGES ) {
		const currentPkgVersion = currentRequire[ pkg.packagist ];
		const previousPkgVersion = previousRequire[ pkg.packagist ];
		if (
			currentPkgVersion &&
			previousPkgVersion &&
			semver.gt( currentPkgVersion, previousPkgVersion )
		) {
			dependencyContributors[ pkg.displayName ] = await getContributors(
				pkg.org,
				pkg.repo,
				`${ pkg.versionPrefix }${ previousPkgVersion }`,
				`${ pkg.versionPrefix }${ currentPkgVersion }`
			);
		}
	}

	return {
		'WooCommerce Core': coreContributors,
		ActionScheduler: dependencyContributors.ActionScheduler || [],
		'WooCommerce Blocks':
			dependencyContributors[ 'WooCommerce Blocks' ] || [],
	};
};
