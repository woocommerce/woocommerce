/**
 * External dependencies.
 */
const child_process = require( 'child_process' );
const fs = require( 'fs' );

/**
 * Generates test matrices to be used in testing workflows.
 * 
 * @param {string} baseRef The base branch to compare against.
 */
module.exports = async function buildTestMatrices( baseRef ) {
    // Support filtering against a list of packages changed since a base ref if one is given.
    const refFilter = baseRef ? `--filter='...[${ baseRef }]'` : '';

    // We can use PNPM to get a list of packages that we should consider for testing.
    const rawProjectList = child_process.execSync(
        `pnpm list ${ refFilter } --filter='!./tools/*' --depth='-1' --parseable`,
        { encoding: 'utf8', }
    );
    // The `--parseable` flag returns a list of package directories separated by newlines.
    const changedProjects = rawProjectList.split( "\n" );

    // Generate the testing matrices for the packages that have changed.
    const testMatrices = {
        php: {
            include: [],
        },
    };
    for ( const projectPath of changedProjects ) {
        // We're only interested in
        let packageFile;
        try {
            const rawPackageFile = fs.readFileSync( `${ projectPath }/package.json`, 'utf8' );
            packageFile = JSON.parse( rawPackageFile );
        } catch ( error ) {
            continue;
        }

        // We only need to generate test matrices for PHP packages because they generally
        // require environments that are mutually exclusive with other PHP projects.
        if ( ! packageFile.scripts[ 'test:php' ] ) {
            continue;
        }

        testMatrices.php.include.push( {
            name: packageFile.name,
            'has-test-environment': true,
            env: {},
        } );
    }

    console.log(testMatrices);

    return testMatrices;
}
