module.exports = () => {
	const {
		GITHUB_BASE_REF,
		GITHUB_RUN_ID,
		PR_NUMBER,
		PR_TITLE,
		SHA,
		TEST_TYPE,
	} = process.env;

	// Slack message blocks
	const blocks = [];
	const dividerBlock = {
		type: 'divider',
	};
	const introBlock = {
		type: 'section',
		text: {
			type: 'mrkdwn',
			text: `${ TEST_TYPE } tests failed on \`${ GITHUB_BASE_REF }\` after merging PR <https://github.com/woocommerce/woocommerce/pull/${ PR_NUMBER }|#${ PR_NUMBER }>`,
		},
	};
	const prTitleBlock = {
		type: 'header',
		text: {
			type: 'plain_text',
			text: PR_TITLE,
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
				url: `https://github.com/woocommerce/woocommerce/pull/${ PR_NUMBER }`,
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
					text: `View merge commit ${ SHA.substring(
						0,
						7
					) } :alphabet-yellow-hash:`,
					emoji: true,
				},
				value: 'view_commit',
				url: `https://github.com/woocommerce/woocommerce/commit/${ SHA }`,
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
				url: `https://github.com/woocommerce/woocommerce/actions/runs/${ GITHUB_RUN_ID }`,
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
				url: `https://woocommerce.github.io/woocommerce-test-reports/pr-merge/${ PR_NUMBER }/${ TEST_TYPE.toLowerCase() }`,
				action_id: 'view-report',
			},
		],
	};

	// Assemble blocks
	blocks.push( dividerBlock );
	blocks.push( introBlock );
	blocks.push( prTitleBlock );
	blocks.push( prButtonBlock );
	blocks.push( mergeCommitBlock );
	blocks.push( githubBlock );

	if ( [ 'e2e', 'api' ].includes( TEST_TYPE.toLowerCase() ) ) {
		blocks.push( reportBlock );
	}

	blocks.push( dividerBlock );

	return { blocks };
};
