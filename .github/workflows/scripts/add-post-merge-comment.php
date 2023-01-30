<?php
/**
 * Script to automatically add a comment to a pull request when it's merged.
 *
 * @package WooCommerce/GithubActions
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions

require_once __DIR__ . '/post-request-shared.php';

/*
 * Get the merger login name.
 */

echo 'Retrieving the merger user name... ';

$get_pr_merged_username_query = "
  node(id: \"$pr_id\") {
    ... on PullRequest {
      timelineItems(first: 1, itemTypes: MERGED_EVENT) {
        edges {
          node {
            ... on MergedEvent {
              actor {
                login
              }
            }
          }
        }
      }
    }
  }";

$result = do_graphql_api_request( $get_pr_merged_username_query );

if ( is_array( $result ) ) {
	if ( empty( $result['errors'] ) ) {
		echo "Ok!\n";
	} else {
		echo "\n*** Errors found while retrieving the merger user name:\n";
		echo var_dump( $result['errors'] );
		return;
	}
} else {
	echo "\n*** Error found while retrieving the merger user name: file_get_contents returned the following:\n";
	echo var_dump( $result );
	return;
}

$merger_user_name = $result['data']['node']['timelineItems']['edges'][0]['node']['actor']['login'];
echo "The pull request was merged by: $merger_user_name\n";

/*
 * Post the comment.
 */

$comment_body = "Hi @$merger_user_name, thanks for merging this pull request. Please take a look at these follow-up tasks you may need to perform:

- [ ] Add the `release: add testing instructions` label";

$add_comment_mutation = "
  addComment(input: {subjectId: \"$pr_id\", body: \"$comment_body\", clientMutationId: \"$github_token\"}) {
    commentEdge {
      node {
        id
        databaseId
        url
      }
    }
  }";

echo 'Publishing the comment... ';

$result = do_graphql_api_request( $add_comment_mutation, true );

if ( is_array( $result ) ) {
	if ( empty( $result['errors'] ) ) {
		echo "Ok!\n";
	} else {
		echo "\n*** Errors found while publishing the comment:\n";
		echo var_dump( $result['errors'] );
		return;
	}
} else {
	echo "\n*** Error found while publishing the comment: file_get_contents returned the following:\n";
	echo var_dump( $result );
	return;
}

$comment_url = $result['data']['addComment']['commentEdge']['node']['url'];
echo "Comment URL: $comment_url\n";

// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions
