/**
 * Finds or creates the documentation issue for a pull request.
 *
 * @param {Object} options              Workflow inputs.
 * @param {Object} options.linearClient Linear SDK client.
 * @param {string} options.teamId       Configured team UUID or exact team key.
 * @param {string} options.labelId      Configured label UUID.
 * @param {Object} options.pr           Pull request from the GitHub event.
 * @param {string} options.repoFullName Repository name, including its owner.
 * @param {Object} options.verdict      Structured documentation verdict.
 * @return {Promise<Object>} Existing or newly created issue and whether it was reused.
 */
module.exports = async ( {
	linearClient,
	teamId,
	labelId,
	pr,
	repoFullName,
	verdict,
} ) => {
	teamId = teamId?.trim();
	labelId = labelId?.trim();
	if ( ! teamId || ! labelId ) {
		throw new Error(
			'Set both LINEAR_DOCS_TEAM_ID and LINEAR_DOCS_AUTO_LABEL before running this workflow.'
		);
	}

	let team;
	try {
		if ( /^[\da-f]{8}(?:-[\da-f]{4}){3}-[\da-f]{12}$/i.test( teamId ) ) {
			team = await linearClient.team( teamId );
		} else {
			const teams = await linearClient.teams( {
				filter: { key: { eq: teamId } },
			} );
			if ( teams.nodes.length === 1 ) {
				team = teams.nodes[ 0 ];
			}
		}
	} catch {
		// SDK errors can contain request details. Report the configuration to check, not its value.
		throw new Error(
			'Cannot read the configured Linear team. Check LINEAR_DOCS_TEAM_ID and that LINEAR_OAUTH_TOKEN can access that team in the same workspace.'
		);
	}
	if ( ! team?.id ) {
		throw new Error(
			'Configured Linear team was not found. Set LINEAR_DOCS_TEAM_ID to its UUID or exact team key and grant LINEAR_OAUTH_TOKEN access to that team.'
		);
	}

	let existingIssues;
	try {
		existingIssues = await linearClient.issues( {
			filter: {
				team: { id: { eq: team.id } },
				description: { contains: pr.html_url },
			},
			includeArchived: true,
		} );
		// A PR title can change. Match its URL, including a boundary so #123 cannot match #1234.
		const prUrl = new RegExp(
			pr.html_url.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + '(?![0-9])'
		);
		for (;;) {
			const issue = existingIssues.nodes.find( ( candidate ) =>
				prUrl.test( candidate.description || '' )
			);
			if ( issue ) {
				return { issue, reused: true };
			}
			if ( ! existingIssues.pageInfo.hasNextPage ) {
				break;
			}
			existingIssues = await existingIssues.fetchNext();
		}
	} catch {
		throw new Error(
			'Cannot check for an existing Linear documentation issue. No issue was created; verify read access before retrying.'
		);
	}

	let label;
	let labelTeam;
	try {
		label = await linearClient.issueLabel( labelId );
		labelTeam = await label.team;
	} catch {
		throw new Error(
			"Cannot read the documentation label. Check LINEAR_DOCS_AUTO_LABEL and the token's access to that label."
		);
	}
	if ( ! label?.id || ( labelTeam && labelTeam.id !== team.id ) ) {
		throw new Error(
			'LINEAR_DOCS_AUTO_LABEL must identify a workspace label or a label belonging to the configured documentation team.'
		);
	}

	const description = [
		'A pull request was flagged as containing user-facing changes that may require documentation updates.',
		'',
		`**Pull request:** ${ pr.html_url }`,
		`**Repository:** ${ repoFullName }`,
		`**Confidence:** ${ verdict.confidence }`,
		`**AI reasoning:** ${ verdict.reason }`,
	].join( '\n' );

	let result;
	let issue;
	try {
		result = await linearClient.createIssue( {
			teamId: team.id,
			title: `Docs update needed: ${ pr.title }`,
			description,
			labelIds: [ label.id ],
		} );
		issue = await result.issue;
	} catch {
		throw new Error(
			'Linear issue creation did not complete successfully. Check create-issue permissions before retrying; the next run will check for an existing issue first.'
		);
	}
	if ( ! result.success || ! issue?.id || ! issue.url ) {
		throw new Error(
			'Linear did not confirm issue creation. The next run will check for an existing issue before trying again.'
		);
	}

	return { issue, reused: false };
};
