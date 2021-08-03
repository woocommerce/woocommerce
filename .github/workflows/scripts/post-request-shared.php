<?php
/**
 * Common code for the post-merge GitHub action scripts.
 *
 * @package WooCommerce/GithubActions
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions

global $repo_owner, $repo_name, $github_token, $graphql_api_url;

/*
 * Grab/process input.
 */

$repo_parts = explode( '/', getenv( 'GITHUB_REPOSITORY' ) );
$repo_owner = $repo_parts[0];
$repo_name  = $repo_parts[1];

$pr_id           = getenv( 'PULL_REQUEST_ID' );
$github_token    = getenv( 'GITHUB_TOKEN' );
$graphql_api_url = getenv( 'GITHUB_GRAPHQL_URL' );


/**
 * Function to query the GitHub GraphQL API.
 *
 * @param string $body The GraphQL-formatted request body, without "query" or "mutation" wrapper.
 * @param bool   $is_mutation True if the request is a mutation, false if it's a query.
 * @return mixed The json-decoded response if a response is received, 'false' (or whatever file_get_contents returns) otherwise.
 */
function do_graphql_api_request( $body, $is_mutation = false ) {
	global $github_token, $graphql_api_url;

	$keyword = $is_mutation ? 'mutation' : 'query';
	$data    = array( 'query' => "$keyword { $body }" );
	$context = stream_context_create(
		array(
			'http' => array(
				'method'  => 'POST',
				'header'  => array(
					'Accept: application/json',
					'Content-Type: application/json',
					'User-Agent: GitHub action to set the milestone for a pull request',
					'Authorization: bearer ' . $github_token,
				),
				'content' => json_encode( $data ),
			),
		)
	);

	$result = file_get_contents( $graphql_api_url, false, $context );
	return is_string( $result ) ? json_decode( $result, true ) : $result;
}

// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions
