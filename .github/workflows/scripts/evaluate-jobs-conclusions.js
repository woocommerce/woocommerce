const {REPOSITORY, RUN_ID, GITHUB_TOKEN, MATRIX} = process.env;

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
	const matrix = JSON.parse(MATRIX).flat();

	console.log('Matrix:', matrix);

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

if (!REPOSITORY) {
	console.error(`Missing REPOSITORY environment variable`);
	process.exit(1);
}
if (!RUN_ID) {
	console.error('Missing RUN_ID environment variables');
	process.exit(1);
}
if (!GITHUB_TOKEN) {
	console.error('Missing GITHUB_TOKEN environment variables');
	process.exit(1);
}
if (!MATRIX) {
	console.error('Missing MATRIX environment variables');
	process.exit(1);
}

evaluateJobs().then(() => {
	console.log('All required jobs passed');
});



