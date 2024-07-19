/* eslint-disable no-console */
const { REPOSITORY, RUN_ID, GITHUB_TOKEN, TEST_MODE } = process.env;
const IGNORED_JOBS = [
	/Evaluate Project Job Statuses/,
	/Report results on Slack/,
	/Test reports/,
	/Create issues for flaky tests/,
];

const isJobRequired = ( job ) => {
	return (
		! job.name.endsWith( '(optional)' ) &&
		! IGNORED_JOBS.some( ( ignoredJobRegex ) =>
			ignoredJobRegex.test( job.name )
		)
	);
};

const fetchJobs = async () => {
	let url = `https://api.github.com/repos/${ REPOSITORY }/actions/runs/${ RUN_ID }/jobs`;
	const nextPattern = /(?<=<)([\S]*)(?=>; rel="Next")/i;
	let pagesRemaining = true;
	const jobs = [];

	while ( pagesRemaining ) {
		console.log( 'Fetching:', url );
		try {
			const response = await fetch( url, {
				headers: {
					'User-Agent': 'node.js',
					Authorization: `Bearer ${ GITHUB_TOKEN }`,
				},
			} );
			const data = await response.json();
			jobs.push( ...data.jobs );

			const linkHeader = response.headers.get( 'link' );
			pagesRemaining =
				linkHeader && linkHeader.includes( `rel=\"next\"` );

			if ( pagesRemaining ) {
				url = linkHeader.match( nextPattern )[ 0 ];
			}
		} catch ( error ) {
			console.error( 'Error:', error );
			// We want to fail if there is an error getting the jobs conclusions
			process.exit( 1 );
		}
	}

	return jobs;
};

const evaluateJobs = async () => {
	let jobs;

	if ( TEST_MODE ) {
		jobs = require( './evaluate-jobs-conclusions-test-data.json' );
	} else {
		jobs = await fetchJobs();
	}
	const nonSuccessfulCompletedJobs = jobs.filter(
		( job ) =>
			job.status === 'completed' &&
			job.conclusion !== 'success' &&
			job.conclusion !== 'skipped'
	);

	console.log( 'Workflow jobs:', jobs.length );
	console.log(
		'Non successful completed jobs:',
		nonSuccessfulCompletedJobs.length
	);

	const failed = [];

	nonSuccessfulCompletedJobs.forEach( ( job ) => {
		const jobPrintName = `'${ job.name }': ${ job.status }, ${ job.conclusion }`;
		if ( isJobRequired( job ) ) {
			console.error( `❌ ${ jobPrintName }, required` );
			failed.push( job.name );
		} else {
			console.warn( `✅ ${ jobPrintName }, optional` );
		}
	} );

	if ( failed.length > 0 ) {
		console.error( 'Failed required jobs:', failed );
		process.exit( 1 );
	}
};

const validateEnvironmentVariables = ( variables ) => {
	if ( TEST_MODE ) {
		return;
	}

	variables.forEach( ( variable ) => {
		if ( ! process.env[ variable ] ) {
			console.error( `Missing ${ variable } environment variable` );
			process.exit( 1 );
		}
	} );
};

validateEnvironmentVariables( [ 'REPOSITORY', 'RUN_ID', 'GITHUB_TOKEN' ] );
evaluateJobs().then( () => {
	console.log( 'All required jobs passed' );
} );
