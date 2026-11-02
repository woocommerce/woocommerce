/**
 * Internal dependencies
 */
import { confirm } from './confirm.mjs';
import { REPOSITORY_PATTERN, createGraphQL, getToken } from './github.mjs';

// GitHub search never returns more than this many results for one query.
export const SEARCH_LIMIT = 1000;

const FIELD = `field { ... on ProjectV2FieldCommon { id name dataType } }`;
const PROJECT_ITEMS = `
	projectItems( first: 50 ) {
		nodes {
			id
			project { id title }
			fieldValues( first: 50 ) {
				nodes {
					... on ProjectV2ItemFieldTextValue { ${ FIELD } text }
					... on ProjectV2ItemFieldNumberValue { ${ FIELD } number }
					... on ProjectV2ItemFieldDateValue { ${ FIELD } date }
					... on ProjectV2ItemFieldSingleSelectValue { ${ FIELD } optionId }
					... on ProjectV2ItemFieldIterationValue { ${ FIELD } iterationId }
				}
			}
		}
	}
`;

// Maps each project field data type to the value property in the API response and in UpdateProjectV2ItemFieldValueInput.
const FIELD_VALUE_KEYS = {
	TEXT: [ 'text', 'text' ],
	NUMBER: [ 'number', 'number' ],
	DATE: [ 'date', 'date' ],
	SINGLE_SELECT: [ 'optionId', 'singleSelectOptionId' ],
	ITERATION: [ 'iterationId', 'iterationId' ],
};

/**
 * Reads the project field values from an issue's `projectItems` nodes.
 *
 * @param {Object[]} projectItems The `projectItems.nodes` of an issue.
 * @return {Object[]} The field values, shaped for UpdateProjectV2ItemFieldValueInput.
 */
export function readProjectFields( projectItems ) {
	const fields = [];
	for ( const item of projectItems ) {
		for ( const fieldValue of item.fieldValues.nodes ) {
			const keys = FIELD_VALUE_KEYS[ fieldValue.field?.dataType ];
			if ( ! keys ) {
				continue;
			}

			fields.push( {
				projectId: item.project.id,
				projectTitle: item.project.title,
				fieldId: fieldValue.field.id,
				fieldName: fieldValue.field.name,
				value: { [ keys[ 1 ] ]: fieldValue[ keys[ 0 ] ] },
			} );
		}
	}

	return fields;
}

/**
 * Compares the field values an issue had before the transfer with its project items after it.
 *
 * @param {Object[]} expectedFields   The fields read from the issue before the transfer.
 * @param {Object[]} projectItems     The `projectItems.nodes` of the issue after the transfer.
 * @param {Object[]} expectedProjects The `{ id, title }` of every project the issue was in, including ones with no field values to restore.
 * @return {{missingProjects: string[], fieldsToRestore: Object[]}} Titles of projects the issue is no longer in, and the fields to set again with the new item IDs.
 */
export function findFieldsToRestore(
	expectedFields,
	projectItems,
	expectedProjects = []
) {
	const itemIds = new Map(
		projectItems.map( ( item ) => [ item.project.id, item.id ] )
	);
	const missingProjects = new Set(
		expectedProjects
			.filter( ( project ) => ! itemIds.has( project.id ) )
			.map( ( project ) => project.title )
	);
	const actualValues = new Map(
		readProjectFields( projectItems ).map( ( field ) => [
			`${ field.projectId }:${ field.fieldId }`,
			JSON.stringify( field.value ),
		] )
	);

	const fieldsToRestore = [];
	for ( const field of expectedFields ) {
		const itemId = itemIds.get( field.projectId );
		if ( ! itemId ) {
			missingProjects.add( field.projectTitle );
		} else if (
			actualValues.get( `${ field.projectId }:${ field.fieldId }` ) !==
			JSON.stringify( field.value )
		) {
			fieldsToRestore.push( { ...field, itemId } );
		}
	}

	return { missingProjects: [ ...missingProjects ], fieldsToRestore };
}

/**
 * Gets the IDs of the labels to add after the transfer, failing if the destination lacks any of them.
 *
 * @param {Function} graphql The GraphQL request function.
 * @param {string}   owner   The destination repository owner.
 * @param {string}   name    The destination repository name.
 * @param {string[]} names   The label names.
 * @return {Promise<string[]>} The label IDs.
 */
async function getLabelIds( graphql, owner, name, names ) {
	if ( names.length === 0 ) {
		return [];
	}

	const allLabels = new Map();
	let cursor = null;
	do {
		const { repository } = await graphql(
			`
				query ($owner: String!, $name: String!, $cursor: String) {
					repository(owner: $owner, name: $name) {
						labels(first: 100, after: $cursor) {
							nodes {
								id
								name
							}
							pageInfo {
								hasNextPage
								endCursor
							}
						}
					}
				}
			`,
			{ owner, name, cursor }
		);

		for ( const label of repository.labels.nodes ) {
			allLabels.set( label.name, label.id );
		}

		const { hasNextPage, endCursor } = repository.labels.pageInfo;
		cursor = hasNextPage ? endCursor : null;
	} while ( cursor !== null );

	return names.map( ( label ) => {
		if ( ! allLabels.has( label ) ) {
			throw new Error(
				`The target repository does not have the label ${ label }.`
			);
		}

		return allLabels.get( label );
	} );
}

/**
 * Gets the issues matching the search query, with their project field values.
 *
 * @param {Function} graphql     The GraphQL request function.
 * @param {string}   searchQuery The GitHub search query.
 * @return {Promise<Object[]>} The issues.
 */
async function getIssues( graphql, searchQuery ) {
	const issues = [];
	let cursor = null;
	do {
		const { search } = await graphql(
			`query ( $searchQuery: String!, $cursor: String ) {
				search( type: ISSUE, query: $searchQuery, first: 100, after: $cursor ) {
					nodes { ... on Issue { id title ${ PROJECT_ITEMS } } }
					pageInfo { hasNextPage endCursor }
				}
			}`,
			{ searchQuery, cursor }
		);

		for ( const issue of search.nodes ) {
			issues.push( {
				id: issue.id,
				title: issue.title,
				projects: issue.projectItems.nodes.map(
					( item ) => item.project
				),
				projectFields: readProjectFields( issue.projectItems.nodes ),
			} );
		}

		const { hasNextPage, endCursor } = search.pageInfo;
		cursor = hasNextPage ? endCursor : null;
	} while ( cursor !== null );

	return issues;
}

/**
 * Sets project field values that did not survive the transfer.
 *
 * @param {Function} graphql The GraphQL request function.
 * @param {Object}   issue   The transferred issue.
 * @return {Promise<string[]>} Problems to report: projects the issue is no longer in, and fields that could not be set. Each field is tried even if another fails, unless a request-level failure stops the rest; that error is rethrown with these problems in `problems`.
 */
async function restoreProjectFields( graphql, issue ) {
	if ( issue.projects.length === 0 ) {
		return [];
	}

	const { node } = await graphql(
		`query ( $id: ID! ) { node( id: $id ) { ... on Issue { ${ PROJECT_ITEMS } } } }`,
		{ id: issue.newId }
	);
	if ( ! node ) {
		throw new Error( 'Could not read the transferred issue' );
	}
	const { missingProjects, fieldsToRestore } = findFieldsToRestore(
		issue.projectFields,
		node.projectItems.nodes,
		issue.projects
	);

	const problems = missingProjects.map(
		( title ) => `no longer in project "${ title }"`
	);
	for ( const field of fieldsToRestore ) {
		const name = `"${ field.fieldName }" in project "${ field.projectTitle }"`;
		try {
			await graphql(
				`
					mutation ($input: UpdateProjectV2ItemFieldValueInput!) {
						updateProjectV2ItemFieldValue(input: $input) {
							projectV2Item {
								id
							}
						}
					}
				`,
				{
					input: {
						projectId: field.projectId,
						fieldId: field.fieldId,
						itemId: field.itemId,
						value: field.value,
					},
				}
			);
			console.log( `Restored ${ name } for "${ issue.title }"` );
		} catch ( error ) {
			problems.push( `restoring ${ name } failed: ${ error.message }` );
			// Every later request would fail the same way, so hand the problems so far to the caller and stop.
			if ( error.requestFailed ) {
				throw Object.assign( error, { problems } );
			}
		}
	}

	return problems;
}

/**
 * Transfers issues from another repository into the destination repository.
 *
 * @param {string}  source               The repository to transfer from, in "owner/repo" format.
 * @param {Object}  options
 * @param {string}  options.destination  The repository to transfer into, in "owner/repo" format.
 * @param {string}  options.searchFilter GitHub search filters for the issues to transfer.
 * @param {string}  [options.labels]     A comma-separated list of labels to add after the transfer.
 * @param {boolean} [options.dryRun]     Run every lookup and print the plan without prompting or changing anything.
 */
export async function transferIssues(
	source,
	{ destination, searchFilter, labels, dryRun = false }
) {
	const [ , owner, name ] = destination.match( /^([^/]+)\/([^/]+)$/ ) ?? [];
	if ( ! owner ) {
		throw new Error(
			'The destination repository must be in the format "owner/repo"!'
		);
	}
	if ( ! REPOSITORY_PATTERN.test( source ) ) {
		throw new Error(
			'The source repository must be in the format "owner/repo"!'
		);
	}
	// GitHub owner names are case-insensitive.
	if ( ! source.toLowerCase().startsWith( owner.toLowerCase() + '/' ) ) {
		throw new Error(
			'The source and target repository must have the same owner!'
		);
	}

	if (
		! dryRun &&
		! ( await confirm(
			`Are you sure you want to transfer issues from ${ source } into ${ destination }?`
		) )
	) {
		throw new Error( 'Cancelled. Nothing was transferred.' );
	}

	const graphql = createGraphQL( await getToken() );
	await graphql( '{ viewer { id } }' );

	const searchQuery = `repo:${ source } is:issue ${ searchFilter }`;
	const { search } = await graphql(
		`
			query ($searchQuery: String!) {
				search(type: ISSUE, query: $searchQuery, first: 0) {
					issueCount
				}
			}
		`,
		{ searchQuery }
	);
	if ( search.issueCount === 0 ) {
		console.log( 'There are no issues to transfer that match this query!' );
		return;
	}

	const issueCount = Math.min( search.issueCount, SEARCH_LIMIT );
	if ( search.issueCount > SEARCH_LIMIT ) {
		console.log(
			`${ search.issueCount } issues match, but GitHub search returns at most ${ SEARCH_LIMIT }. Run the command again afterward to transfer the rest.`
		);
	}
	// Look up the destination and labels before the final prompt, so a typo fails before the scary question.
	const {
		repository: { id: repositoryId },
	} = await graphql(
		`
			query ($owner: String!, $name: String!) {
				repository(owner: $owner, name: $name) {
					id
				}
			}
		`,
		{ owner, name }
	);
	const labelNames = ( labels ?? '' )
		.split( ',' )
		.map( ( label ) => label.trim() )
		.filter( Boolean );
	const labelIds = await getLabelIds( graphql, owner, name, labelNames );

	if (
		! dryRun &&
		! ( await confirm(
			`This will transfer ${ issueCount } issue(s). There is no command to reverse this, are you sure?`
		) )
	) {
		throw new Error( 'Cancelled. Nothing was transferred.' );
	}

	const issues = await getIssues( graphql, searchQuery );

	if ( dryRun ) {
		console.log(
			`Dry run: would transfer ${ issues.length } issue(s) into ${ destination }:`
		);
		for ( const { title, projectFields } of issues ) {
			console.log(
				`- "${ title }" (${ projectFields.length } project field value(s) to check)`
			);
		}
		if ( labelNames.length > 0 ) {
			console.log(
				`Would then add the labels: ${ labelNames.join( ', ' ) }`
			);
		}
		console.log( 'No changes were made.' );
		return;
	}

	let transferred = 0;
	for ( const issue of issues ) {
		console.log( `Transferring "${ issue.title }"` );
		try {
			const { transferIssue } = await graphql(
				`
					mutation ($input: TransferIssueInput!) {
						transferIssue(input: $input) {
							issue {
								id
							}
						}
					}
				`,
				{ input: { issueId: issue.id, repositoryId } }
			);
			issue.newId = transferIssue.issue.id;
			transferred++;
		} catch ( error ) {
			console.log(
				`Failed to transfer "${ issue.title }": ${ error.message }`
			);
			// A rejected token, a rate limit, or a lost connection would fail every remaining transfer too.
			if ( error.requestFailed ) {
				console.log(
					'Stopping the transfer; running post-transfer tasks for the issues already moved.'
				);
				break;
			}
		}
	}

	const failures = [];
	if ( transferred > 0 ) {
		// Give GitHub time to finish moving labels and project items before we touch the new issues.
		console.log( 'Waiting for GitHub to process transfers' );
		await new Promise( ( resolve ) => setTimeout( resolve, 25000 ) );

		console.log( 'Running post-transfer tasks' );
	}
	// Each step is tried on its own, so one failure does not skip the other or the remaining issues,
	// unless the failure is one that every later request would hit too.
	const moved = issues.filter( ( { newId } ) => newId );
	for ( const [ index, issue ] of moved.entries() ) {
		let requestFailure;
		try {
			for ( const problem of await restoreProjectFields(
				graphql,
				issue
			) ) {
				failures.push( `${ issue.title }: ${ problem }` );
			}
		} catch ( error ) {
			const problems = error.problems ?? [
				`restoring project fields failed: ${ error.message }`,
			];
			for ( const problem of problems ) {
				failures.push( `${ issue.title }: ${ problem }` );
			}
			requestFailure = error.requestFailed ? error : undefined;
		}
		// When the project restore is what stopped, this issue's remaining fields and labels were never tried.
		const currentIssueUnfinished = Boolean( requestFailure );

		if ( labelIds.length > 0 && ! requestFailure ) {
			try {
				await graphql(
					`
						mutation ($input: AddLabelsToLabelableInput!) {
							addLabelsToLabelable(input: $input) {
								clientMutationId
							}
						}
					`,
					{ input: { labelableId: issue.newId, labelIds } }
				);
			} catch ( error ) {
				failures.push(
					`${ issue.title }: adding labels failed: ${ error.message }`
				);
				requestFailure = error.requestFailed ? error : undefined;
			}
		}

		if ( requestFailure ) {
			const skipped = moved.slice(
				currentIssueUnfinished ? index : index + 1
			);
			const tasks =
				labelIds.length > 0
					? 'project fields and labels'
					: 'project fields';
			if ( skipped.length > 0 ) {
				failures.push(
					`Stopped post-transfer tasks, so these transferred issues still need their ${ tasks } checked: ${ skipped
						.map( ( { title } ) => `"${ title }"` )
						.join( ', ' ) }`
				);
			}
			break;
		}
	}

	if ( failures.length > 0 ) {
		console.log(
			'Post-transfer tasks failed for:\n' + failures.join( '\n' )
		);
	}
	const count = `${ transferred }/${ issues.length } issues`;
	if ( transferred < issues.length || failures.length > 0 ) {
		throw new Error( `Transferred ${ count }. See the failures above.` );
	}
	console.log( `Successfully transferred ${ count }.` );
}
