const {REPOSITORY, RUN_ID, GITHUB_TOKEN} = process.env;
const IGNORE_JOBS = ['Evaluate Project Job Statuses', 'Report e2e tests results', 'Report API tests results'];

const fetchJobs = async () => {
	try {
		const response = await fetch(`https://api.github.com/repos/${REPOSITORY}/actions/runs/${RUN_ID}/jobs`, {
			headers: {
				'User-Agent': 'node.js',
			},
		});
		const data = await response.json();
		return data.jobs;
	} catch (error) {
		console.error('Error:', error);
		// We want to fail if there is an error getting the jobs conclusions
		process.exit(1);
	}
};

const evaluateJobs = async () => {
	const jobs = await fetchJobs();
	const nonSuccessfulCompletedJobs = jobs.filter(job =>
		job.status === 'completed' && (job.conclusion !== 'success' && job.conclusion !== 'skipped')
	);

	console.log('Jobs:', jobs.length);
	console.log('nonSuccessfulCompletedJobs:', nonSuccessfulCompletedJobs.length);

	const failed = [];

	nonSuccessfulCompletedJobs.forEach(job => {
		console.log(`Checking job '${job.name}': ${job.status}, ${job.conclusion}`);
		if (isJobRequired(job)) {
			console.error(`Job '${job.name}' is required and was not successful`);
			failed.push(job.name);
		}
	})

	if (failed.length > 0) {
		console.error('Failed required jobs:', failed);
		process.exit(1);
	}
}

const isJobRequired = (job) => {
	return !job.name.endsWith('(optional)') && !IGNORE_JOBS.includes(job.name)
}

const isJobCompletedAndFailed = (job) => {
	return job.status === 'completed' && (job.conclusion !== 'success' && job.conclusion !== 'skipped');
}

const validateEnvironmentVariables = (variables) => {
	variables.forEach((variable) => {
		if (!process.env[variable]) {
			console.error(`Missing ${variable} environment variable`);
			process.exit(1);
		}
	});
}

validateEnvironmentVariables(['REPOSITORY', 'RUN_ID', 'GITHUB_TOKEN', 'MATRIX']);
evaluateJobs().then(() => {
	console.log('All required jobs passed');
});



