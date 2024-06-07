/* eslint-disable no-console */
const { REPOSITORY, RUN_ID, GITHUB_TOKEN, TEST_MODE } = process.env;
const IGNORED_JOBS = [
	'Evaluate Project Job Statuses',
	'Report tests results',
];

const isJobRequired = ( job ) => {
	return (
		! job.name.endsWith( '(optional)' ) &&
		! IGNORED_JOBS.includes( job.name )
	);
};

const fetchJobs = async () => {
	try {
		const response = await fetch(
			`https://api.github.com/repos/${ REPOSITORY }/actions/runs/${ RUN_ID }/jobs`,
			{
				headers: {
					'User-Agent': 'node.js',
					Authorization: `Bearer ${ GITHUB_TOKEN }`,
				},
			}
		);
		const data = await response.json();
		return data.jobs;
	} catch ( error ) {
		console.error( 'Error:', error );
		// We want to fail if there is an error getting the jobs conclusions
		process.exit( 1 );
	}
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
