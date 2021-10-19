module.exports = {
	'*.scss': [ 'npm run lint:css-fix' ],
	'client/**/*.(t|j)s?(x)': [
		'wp-scripts format-js',
		'wp-scripts lint-js',
		'npm run test-staged',
	],
	'packages/**/*.(t|j)s?(x)': ( packageFiles ) => {
		const globalScripts = [
			`wp-scripts format-js ${ packageFiles.join( ' ' ) }`,
			`wp-scripts lint-js ${ packageFiles.join( ' ' ) }`,
		];

		const filesByPackage = packageFiles.reduce(
			( packages, packageFile ) => {
				const packageNameMatch = packageFile.match(
					/\/packages\/([a-z0-9\-]+)\//
				);

				if ( ! packageNameMatch ) {
					return packages;
				}

				const packageName = packageNameMatch[ 1 ];

				if ( Array.isArray( packages[ packageName ] ) ) {
					packages[ packageName ].push( packageFile );
				} else {
					packages[ packageName ] = [ packageFile ];
				}

				return packages;
			},
			{}
		);

		const workspaceScripts = Object.keys( filesByPackage ).map(
			( packageName ) =>
				`lerna --scope @woocommerce/${ packageName } run test-staged -- ${ filesByPackage[
					packageName
				].join( ' ' ) }`
		);

		return globalScripts.concat( workspaceScripts );
	},
	'*.php': [ 'php -d display_errors=1 -l', 'composer run-script phpcs' ],
};
