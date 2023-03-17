const { ALLURE_REPORT_DIR, ALLURE_RESULTS_DIR } = process.env;

if ( ! ( ALLURE_REPORT_DIR || ALLURE_RESULTS_DIR ) ) {
	const errorMessage =
		'You tried to use the "upload-allure-files-to-bucket" action without specifying the path to the allure-results and/or allure-report folders.\n' +
		'To use this action, set the paths using the ALLURE_REPORT_DIR and ALLURE_RESULTS_DIR environment variables.';

	core.setFailed( errorMessage );
}
