/**
 *
 * Use this configuration file to set certain Allure options.
 *
 */

// ALLURE_OUTPUT_DIR is the environment variable for the directory where you want the "allure-results" and "allure-report" folders to be generated in.
const { ALLURE_OUTPUT_DIR } = process.env;

// If ALLURE_OUTPUT_DIR was specified, use it as the target for the "allure-results" directory.
if ( ALLURE_OUTPUT_DIR ) {
	reporter.allure.setOptions( {
		targetDir: `${ ALLURE_OUTPUT_DIR }/allure-results`,
	} );
}
