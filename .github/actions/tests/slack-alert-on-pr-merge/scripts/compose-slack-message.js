module.exports = ( { github, core } ) => {
	// Get variables from 'github' and 'inputs' objects
	const baseRef = github.base_ref;
	const prNumber = github.event.pull_request.number;
	const prTitle = github.event.pull_request.title;
	const runId = github.run_id;
	const sha = github.event.pull_request.merge_commit_sha;
	const testType = core.getInput( 'test-type' );

	// Slack message blocks
	const blocks = [];
	const dividerBlock = {
		type: 'divider',
	};
	const introBlock = {
		type: 'section',
		text: {
			type: 'mrkdwn',
			text: `${ testType.toUpperCase() } tests failed on \`${ baseRef }\` after merging PR <https://github.com/woocommerce/woocommerce/pull/${ prNumber }|#${ prNumber }>`,
		},
	};
	const prTitleBlock = {
		type: 'header',
		text: {
			type: 'plain_text',
			text: prTitle,
			emoji: true,
		},
	};
	const prButtonBlock = {
		type: 'actions',
		elements: [
			{
				type: 'button',
				text: {
					type: 'plain_text',
					text: 'View pull request :pr-merged:',
					emoji: true,
				},
				value: 'view_pr',
				url: `https://github.com/woocommerce/woocommerce/pull/${ prNumber }`,
				action_id: 'view-pr',
			},
		],
	};
	const mergeCommitBlock = {
		type: 'actions',
		elements: [
			{
				type: 'button',
				text: {
					type: 'plain_text',
					text: `View merge commit ${ sha.substring(
						0,
						7
					) } :alphabet-yellow-hash:`,
					emoji: true,
				},
				value: 'view_commit',
				url: `https://github.com/woocommerce/woocommerce/commit/${ sha }`,
				action_id: 'view-commit',
			},
		],
	};
	const githubBlock = {
		type: 'actions',
		elements: [
			{
				type: 'button',
				text: {
					type: 'plain_text',
					text: 'View GitHub run log :github:',
					emoji: true,
				},
				value: 'view_github',
				url: `https://github.com/woocommerce/woocommerce/actions/runs/${ runId }`,
				action_id: 'view-github',
			},
		],
	};
	const reportBlock = {
		type: 'actions',
		elements: [
			{
				type: 'button',
				text: {
					type: 'plain_text',
					text: 'View test report :colorful-bar-chart:',
					emoji: true,
				},
				value: 'view_report',
				url: `https://woocommerce.github.io/woocommerce-test-reports/pr-merge/${ prNumber }/${ testType.toLowerCase() }/index.html`,
				action_id: 'view-report',
			},
		],
	};

	// Assemble blocks
	blocks.push( introBlock );
	blocks.push( dividerBlock );
	blocks.push( prTitleBlock );
	blocks.push( prButtonBlock );
	blocks.push( mergeCommitBlock );
	blocks.push( githubBlock );

	if ( [ 'e2e', 'api' ].includes( testType.toLowerCase() ) ) {
		blocks.push( reportBlock );
	}

	return { blocks };
};
