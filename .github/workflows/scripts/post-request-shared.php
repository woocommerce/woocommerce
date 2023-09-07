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
$github_api_url  = getenv( 'GITHUB_API_URL' );
$graphql_api_url = getenv( 'GITHUB_GRAPHQL_URL' );


/**
 * Function to get the latest milestone.
 *
 * @param bool $use_latest_when_null When true, the function returns the latest milestone regardless of release branch status.
 * @return string The title of the latest milestone.
 */
function get_latest_milestone_from_api( $use_latest_when_null = false ) {
	global $repo_owner, $repo_name;

	echo 'Getting the list of milestones...' . PHP_EOL;

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

	echo 'Latest open milestone: ' . $milestones[0]['title'] . PHP_EOL;

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
	if ( $use_latest_when_null && is_null( $chosen_milestone ) ) {
		echo 'WARNING: No milestone without release branch found, the newest one will be assigned.' . PHP_EOL;
		$chosen_milestone = $milestones[0];
	}

	return $chosen_milestone;
}

/**
 * Function to get the last major.minor version with a release from the API.
 *
 * @return string Returns the latest version with a release formatted as "major.minor".
 */
function get_latest_version_with_release() {
	global $repo_owner, $repo_name;

	echo 'Getting the list of releases...' . PHP_EOL;

	$query = "
	repository(owner:\"$repo_owner\", name:\"$repo_name\") {
		releases(first: 25, orderBy: { field: CREATED_AT, direction: DESC}) {
			nodes {
				tagName
			}
		}
	}
	";
	$json     = do_graphql_api_request( $query );
	$releases = $json['data']['repository']['releases']['nodes'];
	$releases = array_map(
		function( $x ) {
			return 1 === preg_match( '/^\d+\.\d+\.\d+/D', $x['tagName'] ) ? $x : null;
		},
		$releases
	);
	$releases = array_filter( $releases );
	usort(
		$releases,
		function( $a, $b ) {
			return version_compare( $b['tagName'], $a['tagName'] );
		}
	);

	$major_minor = preg_replace( '/(^\d+\.\d+).*?$/', '\1', $releases[0]['tagName'] );
	echo 'Most recent version with a release: ' . $major_minor . PHP_EOL;

	return $major_minor;
}

/**
 * Function to retrieve the sha1 reference for a given branch name.
 *
 * @param string $branch The name of the branch.
 * @return string Returns the name of the branch, or a falsey value on error.
 */
function get_ref_from_branch( $branch ) {
	global $repo_owner, $repo_name;
	$query = "
	repository(owner:\"$repo_owner\", name:\"$repo_name\") {
	  ref(qualifiedName: \"refs/heads/{$branch}\") {
	  	target {
	  	  ... on Commit {
	  	  	history(first: 1) {
	  	  	  edges{ node{ oid } }
	  	  	}
	  	  }
	  	}
	  }
	}
	";
	$result = do_graphql_api_request( $query );

	// Warnings suppressed here because traversing this level of arrays with isset / is_array checks would be messy.
	return @$result['data']['repository']['ref']['target']['history']['edges'][0]['node']['oid'];
}

/**
 * Function to create milestone using the GitHub REST API.
 *
 * @param string $title The title of the milestone to be created.
 * @return bool True on success, False otherwise.
 */
function create_github_milestone( $title ) {
	global $repo_owner, $repo_name;

	$result = do_github_api_post_request( "/repos/{$repo_owner}/{$repo_name}/milestones", array(
		'title' => $title,
	) );

	return is_array( $result ) && $result['title'] === $title;
}

/**
 * Function to create branch using the GitHub REST API.
 *
 * @param string $branch The branch to be created.
 * @param string $sha The sha1 reference for the branch.
 * @return bool True on success, False otherwise.
 */
function create_github_branch( $branch, $sha ) {
	global $repo_owner, $repo_name;

	$ref = "refs/heads/{$branch}";
	$result = do_github_api_post_request( "/repos/{$repo_owner}/{$repo_name}/git/refs", array(
		'ref' => $ref,
		'sha' => $sha,
	) );

	return is_array( $result ) && $result['ref'] === $ref;
}

/**
 * Function to create branch using the GitHub REST API from an existing branch.
 *
 * @param string $source The branch from which to create.
 * @param string $target The branch to be created.
 * @return bool True on success, False otherwise.
 */
function create_github_branch_from_branch( $source, $target ) {
	$ref = get_ref_from_branch( $source );
	if ( ! $ref ) {
		return false;
	}
	return create_github_branch( $target, $ref );
}

/**
 * Function to do a GitHub API POST Request.
 *
 * @param array $request_url
 * @param array $body The body of the request to be json encoded.
 * @return mixed The json-decoded response if a response is received, 'false' (or whatever file_get_contents returns) otherwise.
 */
function do_github_api_post_request( $request_path, $body ) {
	global $github_token, $github_api_url, $github_api_response_code;

	$context = stream_context_create(
		array(
			'http' => array(
				'method'  => 'POST',
				'header'  => array(
					'Accept: application/vnd.github.v3+json',
					'Content-Type: application/json',
					'User-Agent: GitHub Actions for creation of milestones',
					'Authorization: bearer ' . $github_token,
				),
				'content' => json_encode( $body ),
			),
		)
	);

	$full_request_url = rtrim( $github_api_url, '/' ) . '/' . ltrim( $request_path, '/' );
	$result = @file_get_contents( $full_request_url, false, $context );

	// Verify that the post request was sucessful.
	$status_line = $http_response_header[0];
	preg_match( "/^HTTPS?\/\d\.\d\s+(\d{3})\s+/i", $status_line, $matches );
	$github_api_response_code = $matches[1];
	if ( '2' !== substr( $github_api_response_code, 0, 1 ) ) {
		return false;
	}

	return is_string( $result ) ? json_decode( $result, true ) : $result;
}

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
