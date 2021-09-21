<?php
/**
 * Script to automatically assign a milestone to a pull request when it's merged.
 *
 * @package WooCommerce/GithubActions
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions

require_once __DIR__ . '/post-request-shared.php';

/*
 * Select the milestone to be added:
 *
 * 1. Get the first 10 milestones sorted by creation date descending.
 *    (we'll never have more than 2 or 3 active milestones but let's get 10 to be sure).
 * 2. Discard those not open or whose title is not a proper version number ("X.Y.Z").
 * 3. Sort descending using version_compare.
 * 4. Get the oldest one that does not have a corresponding "release/X.Y" branch.
 */

echo "Getting the list of milestones...\n";

$query      = "
  repository(owner:\"$repo_owner\", name:\"$repo_name\") {
    milestones(first: 10, states: [OPEN], orderBy: {field: CREATED_AT, direction: DESC}) {
      nodes {
        id
        title
        state
      }
    }
  }
";
$json       = do_graphql_api_request( $query );
$milestones = $json['data']['repository']['milestones']['nodes'];
$milestones = array_map(
	function( $x ) {
		return 1 === preg_match( '/^\d+\.\d+\.\d+$/D', $x['title'] ) ? $x : null;
	},
	$milestones
);
$milestones = array_filter( $milestones );
usort(
	$milestones,
	function( $a, $b ) {
		return version_compare( $b['title'], $a['title'] );
	}
);

echo 'Latest open milestone: ' . $milestones[0]['title'] . "\n";

$chosen_milestone = null;
foreach ( $milestones as $milestone ) {
	$milestone_title_parts    = explode( '.', $milestone['title'] );
	$milestone_release_branch = 'release/' . $milestone_title_parts[0] . '.' . $milestone_title_parts[1];

	$query  = "
    repository(owner:\"$repo_owner\", name:\"$repo_name\") {
      ref(qualifiedName: \"refs/heads/$milestone_release_branch\") {
        id
      }
    }
  ";
	$result = do_graphql_api_request( $query );

	if ( is_null( $result['data']['repository']['ref'] ) ) {
		$chosen_milestone = $milestone;
	} else {
		break;
	}
}

// If all the milestones have a release branch, just take the newest one.
if ( is_null( $chosen_milestone ) ) {
	echo "WARNING: No milestone without release branch found, the newest one will be assigned.\n";
	$chosen_milestone = $milestones[0];
}

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
