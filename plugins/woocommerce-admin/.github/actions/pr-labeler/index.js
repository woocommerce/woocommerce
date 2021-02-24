const core = require( '@actions/core' );
const github = require( '@actions/github' );

const getPRNumber = () => {
	const pr = github.context.payload.pull_request;
	return pr && pr.number ? pr.number : null;
};

const addLabel = async ( client, label, prNumber ) => {
	await client.issues.addLabels( {
		owner: github.context.repo.owner,
		repo: github.context.repo.repo,
		issue_number: prNumber,
		labels: [ label ],
	} );
};

const removeLabel = async ( client, label, prNumber ) => {
	await client.issues.removeLabel( {
		owner: github.context.repo.owner,
		repo: github.context.repo.repo,
		issue_number: prNumber,
		name: label,
	} );
};

async function run() {
	try {
		const prNumber = getPRNumber();

		if ( ! prNumber ) {
			console.log( 'This action only supports pull requests.' );
			return;
		}

		const token = core.getInput( 'access_token', { required: true } );
		const client = github.getOctokit( token );
		const label = core.getInput( 'label', { required: true } );
		const action = core.getInput( 'action', { required: true } );

		const { data: pullRequest } = await client.pulls.get( {
			owner: github.context.repo.owner,
			repo: github.context.repo.repo,
			pull_number: prNumber,
		} );

		const prHasLabel = pullRequest.labels.some( ( l ) => l.name === label );

		if ( action === 'add' && ! prHasLabel ) {
			await addLabel( client, label, prNumber );
		} else if ( action === 'remove' && prHasLabel ) {
			await removeLabel( client, label, prNumber );
		}
	} catch ( e ) {
		core.error( e );
		core.setFailed( e.message );
	}
}

run();
