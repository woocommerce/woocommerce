/**
 * Update the title in the `summary.json` file of the Allure report.
 *
 * @param {string} allureReportDir Path to allure-report dir
 * @param {string} reportTitle Title to appear on Allure Overview page
 */
module.exports = ( allureReportDir, reportTitle ) => {
	const fs = require( 'fs' );
	const summaryPath = `${ allureReportDir }/widgets/summary.json`;
	const summary = require( summaryPath );

	// Set new report title
	summary.reportName = reportTitle;

	// Rewrite json
	const updatedSummary = JSON.stringify( summary, null, 2 );
	fs.writeFileSync( summaryPath, updatedSummary );
};
