const {REPOSITORY, RUN_ID, GITHUB_TOKEN} = process.env;

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

	const failed = [];

	jobs.forEach(job => {
		if (isJobRequired(job) && isJobFailed(job)) {
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
	return !job.name.endsWith('(optional)');
}

const isJobFailed = (job) => {
	return job.conclusion !== 'success' && job.conclusion !== 'skipped';
}

if (!REPOSITORY || !RUN_ID || !GITHUB_TOKEN) {
	console.error('Missing required environment variables');
	process.exit(1);
}

evaluateJobs().then(() => {
	console.log('All required jobs passed');
});



