/**
 *
 * Use this configuration file to set certain Allure options.
 *
 */

// API_TEST_REPORT_DIR is the environment variable for the directory where you want the "allure-results" and "allure-report" folders to be generated in.
const { API_TEST_REPORT_DIR } = process.env;

// If API_TEST_REPORT_DIR was specified, use it as the target for the "allure-results" directory.
if ( API_TEST_REPORT_DIR ) {
	reporter.allure.setOptions( {
		targetDir: `${ API_TEST_REPORT_DIR }/allure-results`,
	} );
}
