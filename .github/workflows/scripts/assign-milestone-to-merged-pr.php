<?php
/**
 * Script to automatically assign a milestone to a pull request when it's merged.
 *
 * @package WooCommerce/GithubActions
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions

require_once __DIR__ . '/post-request-shared.php';

$chosen_milestone = get_latest_milestone_from_api( true );

echo 'Milestone that will be assigned: ' . $chosen_milestone['title'] . "\n";

if ( getenv( 'DRY_RUN' ) ) {
	echo "Dry run, skipping the actual milestone assignment\n";
	return;
}

/*
 * Assign the milestone to the pull request.
 */

echo 'Assigning the milestone to the pull request... ';

$milestone_id = $chosen_milestone['id'];
$mutation     = "
  updatePullRequest(input: {pullRequestId: \"$pr_id\", milestoneId: \"$milestone_id\"}) {
    clientMutationId
  }
";

$result = do_graphql_api_request( $mutation, true );
if ( is_array( $result ) ) {
	if ( empty( $result['errors'] ) ) {
		echo "Ok!\n";
	} else {
		echo "\n*** Errors found while assigning the milestone:\n";
		echo var_dump( $result['errors'] );
	}
} else {
	echo "\n*** Error found while assigning the milestone: file_get_contents returned the following:\n";
	echo var_dump( $result );
}

// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions
