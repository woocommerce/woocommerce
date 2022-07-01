const { GITHUB_WORKSPACE } = process.env;

module.exports = async ( { core } ) => {
	await core.summary
		.addHeading( 'Test Results' )
		.addTable( [
			[
				{ data: 'File', header: true },
				{ data: 'Result', header: true },
			],
			[ 'foo.js', 'Pass ' ],
			[ 'bar.js', 'Fail ' ],
			[ 'test.js', 'Pass ' ],
		] )
		.addLink( 'Link to the full API test report.', 'https://github.com' )
		.addLink( 'Link to the full E2E test report.', 'https://github.com' )
		.write();
};
