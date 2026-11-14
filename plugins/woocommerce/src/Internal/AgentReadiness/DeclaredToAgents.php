<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * What the store declares to agents: protocols and files that are found or not found,
 * never pass or fail, and never count as a problem. Runs only in the background scan.
 *
 * `found` is null when the store could not be read.
 *
 * @since 11.4.0
 */
class DeclaredToAgents {

	/**
	 * Every item with whether the store serves it.
	 *
	 * @param StoreResponses $responses Responses of the scan.
	 * @return array<int, array{id:string,name:string,found:bool|null}>
	 */
	public function all( StoreResponses $responses ): array {
		$responses->home_page();
		$items = array();
		foreach ( $this->files() as $id => $file ) {
			$items[] = array(
				'id'    => $id,
				'name'  => $file[0],
				'found' => self::file_served( $responses, $file[1] ),
			);
		}
		foreach ( $this->documents() as $id => $document ) {
			$items[] = array(
				'id'    => $id,
				'name'  => $document[0],
				'found' => self::document_served( $responses, $document[1], $document[2] ),
			);
		}
		$home  = $responses->home_page();
		$page  = $responses->product_page();
		$lines = array(
			'sameas-links'                => array( __( 'sameAs links in structured data', 'woocommerce' ), self::json_ld_has( $home, 'sameAs' ) ),
			'site-search-action'          => array( __( 'SearchAction for on-site search', 'woocommerce' ), self::has_search_action( $home ) ),
			'webmcp-forms-schema'         => array( __( 'Forms that expose declarative WebMCP', 'woocommerce' ), self::page_has_tag( array( $home, $page ), array( 'FORM' ), static fn( \WP_HTML_Tag_Processor $tag ): bool => null !== $tag->get_attribute( 'toolname' ) && null !== $tag->get_attribute( 'tooldescription' ) ) ),
			'markdown-pages'              => array( __( 'Markdown version of pages (Accept: text/markdown)', 'woocommerce' ), self::is_markdown( self::markdown_answer( $responses ) ) ),
			'markdown-alternate-link'     => array( __( 'Pages link to their Markdown version', 'woocommerce' ), self::page_has_tag( array( $home, $page ), array( 'LINK' ), static fn( \WP_HTML_Tag_Processor $tag ): bool => in_array( 'alternate', self::tokens( $tag->get_attribute( 'rel' ) ), true ) && 0 === stripos( trim( (string) $tag->get_attribute( 'type' ) ), 'text/markdown' ) ) ),
			'markdown-frontmatter'        => array( __( 'Markdown versions start with frontmatter', 'woocommerce' ), self::has_frontmatter( self::markdown_answer( $responses ) ) ),
			'link-header-agent-resources' => array( __( 'Link header pointing to the API catalog or llms.txt', 'woocommerce' ), self::links_agent_resources( $home ) ),
			'code-block-language'         => array( __( 'Code blocks tagged with their language', 'woocommerce' ), self::page_has_tag( array( $home, $page ), array( 'PRE', 'CODE' ), static fn( \WP_HTML_Tag_Processor $tag ): bool => 1 === preg_match( '/(?:^|\s)(?:language|lang)-[\w+#-]+/i', (string) $tag->get_attribute( 'class' ) ) ) ),
			'editorial-metadata'          => array( __( 'Author and publish date', 'woocommerce' ), self::has_editorial_metadata( array( $home, $page ) ) ),
			'site-search-autocomplete'    => array( __( 'On-site search with autocomplete', 'woocommerce' ), self::page_has_tag( array( $home ), array( 'INPUT' ), array( self::class, 'is_autocomplete_search' ) ) ),
			'mcp-server-card'             => array( __( 'MCP server card', 'woocommerce' ), self::either( self::document_served( $responses, '/.well-known/mcp/server-card.json', array( self::class, 'is_mcp_server_card' ) ), self::document_served( $responses, '/.well-known/mcp.json', array( self::class, 'is_mcp_server_card' ) ) ) ),
			// Not read yet: DNS-AID needs a DNS lookup, and a feed product sync is set up outside the store's pages.
			'dns-aid'                     => array( __( 'DNS for AI Discovery (DNS-AID)', 'woocommerce' ), null ),
			'product-feed-automation'     => array( __( 'Automated product feed', 'woocommerce' ), null ),
		);
		foreach ( $lines as $id => $line ) {
			$items[] = array(
				'id'    => $id,
				'name'  => $line[0],
				'found' => $line[1],
			);
		}
		return $items;
	}

	/**
	 * Items served as a file at a known path, by id.
	 *
	 * @return array<string, array{0:string,1:string}> Name and path from the host root.
	 */
	private function files(): array {
		return array(
			'ai-txt'       => array( __( 'ai.txt usage file', 'woocommerce' ), '/ai.txt' ),
			'sitemap-md'   => array( __( 'Markdown sitemap', 'woocommerce' ), '/sitemap.md' ),
			'agents-md'    => array( __( 'AGENTS.md', 'woocommerce' ), '/AGENTS.md' ),
			'security-txt' => array( __( 'security.txt', 'woocommerce' ), '/.well-known/security.txt' ),
			'auth-md'      => array( __( 'auth.md agent sign-in instructions', 'woocommerce' ), '/auth.md' ),
			'llms-full'    => array( __( 'llms-full.txt', 'woocommerce' ), '/llms-full.txt' ),
		);
	}

	/**
	 * Items served as a JSON document at a known path, by id. A 200 alone doesn't count: some
	 * stores answer any path with a JSON error, so each document has to have what its spec requires.
	 *
	 * @return array<string, array{0:string,1:string,2:callable(array<mixed>):bool}> Name, path from the host root, and test.
	 */
	private function documents(): array {
		$has = static fn( string ...$keys ): callable => static fn( array $document ): bool => array() === array_diff( $keys, array_keys( array_filter( $document ) ) );
		return array(
			'api-catalog'              => array( __( 'API catalog', 'woocommerce' ), '/.well-known/api-catalog', $has( 'linkset' ) ),
			'tdmrep'                   => array( __( 'TDMRep text-and-data-mining reservation', 'woocommerce' ), '/.well-known/tdmrep.json', static fn( array $document ): bool => isset( $document[0]['location'] ) ),
			'a2a-agent-card'           => array( __( 'A2A Agent Card', 'woocommerce' ), '/.well-known/agent-card.json', $has( 'name', 'skills' ) ),
			'agent-skills-index'       => array( __( 'Agent Skills index', 'woocommerce' ), '/.well-known/agent-skills/index.json', $has( 'skills' ) ),
			'openapi-spec'             => array( __( 'OpenAPI description', 'woocommerce' ), '/openapi.json', $has( 'openapi' ) ),
			'agents-json'              => array( __( 'agents.json', 'woocommerce' ), '/.well-known/agents.json', $has( 'agentsJson', 'flows' ) ),
			'web-bot-auth'             => array( __( 'Web Bot Auth key directory', 'woocommerce' ), '/.well-known/http-message-signatures-directory', $has( 'keys' ) ),
			'oauth-discovery'          => array( __( 'OAuth authorization server metadata', 'woocommerce' ), '/.well-known/oauth-authorization-server', $has( 'issuer' ) ),
			'oauth-protected-resource' => array( __( 'OAuth Protected Resource metadata', 'woocommerce' ), '/.well-known/oauth-protected-resource', $has( 'resource' ) ),
			'poppy-json'               => array( __( 'Personal Agent Protocol (poppy.json)', 'woocommerce' ), '/.well-known/poppy.json', array( self::class, 'is_poppy_document' ) ),
			'acp-support'              => array( __( 'Agentic Commerce Protocol (ACP) declared', 'woocommerce' ), '/.well-known/acp.json', static fn( array $document ): bool => 'acp' === ( $document['protocol']['name'] ?? '' ) ),
			'ard-manifest'             => array( __( 'Agentic Resource Discovery manifest', 'woocommerce' ), '/.well-known/ard.json', static fn( array $document ): bool => is_array( $document['entries'] ?? null ) ),
			'ai-catalog'               => array( __( 'AI catalog discovery', 'woocommerce' ), '/.well-known/ai-catalog.json', static fn( array $document ): bool => isset( $document['specVersion'] ) && is_array( $document['entries'] ?? null ) ),
			'mpp'                      => array( __( 'Machine Payments Protocol (MPP)', 'woocommerce' ), '/openapi.json', array( self::class, 'has_payment_info' ) ),
		);
	}

	/**
	 * Whether a document is a Personal Agent Protocol one for this store: it names its version and this host.
	 *
	 * @param array<mixed> $document Decoded document.
	 */
	private static function is_poppy_document( array $document ): bool {
		$domain = $document['organization']['domain'] ?? null;
		return ! empty( $document['protocol_version'] ) && is_string( $domain ) && wp_parse_url( home_url(), PHP_URL_HOST ) === $domain;
	}

	/**
	 * Whether an OpenAPI document has an operation with Machine Payments Protocol payment information.
	 *
	 * @param array<mixed> $document Decoded document.
	 */
	private static function has_payment_info( array $document ): bool {
		foreach ( (array) ( $document['paths'] ?? array() ) as $operations ) {
			foreach ( (array) $operations as $operation ) {
				if ( is_array( $operation ) && isset( $operation['x-payment-info'] ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Whether any of the pages that loaded has a tag that matches. Tags are read as the browser
	 * reads them, so markup inside a comment or a script doesn't count. Null when none loaded.
	 *
	 * @param array<int, array{status:int,body:string,headers:array<string,string>}|null> $pages     Pages, null for one that didn't load.
	 * @param array<int, string>                                                          $tag_names Upper-case tag names.
	 * @param callable(\WP_HTML_Tag_Processor): bool                                      $matches   Test for one tag.
	 */
	private static function page_has_tag( array $pages, array $tag_names, callable $matches ): ?bool {
		$loaded = array_filter( $pages );
		if ( array() === $loaded ) {
			return null;
		}
		foreach ( $loaded as $page ) {
			$tags = new \WP_HTML_Tag_Processor( $page['body'] );
			while ( $tags->next_tag() ) {
				if ( in_array( $tags->get_tag(), $tag_names, true ) && ! $tags->is_tag_closer() && $matches( $tags ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * The space-separated tokens of an attribute value, lower case.
	 *
	 * @param mixed $value Attribute value.
	 * @return array<int, string>
	 */
	private static function tokens( $value ): array {
		return is_string( $value ) ? array_values( array_filter( explode( ' ', strtolower( (string) preg_replace( '/\s+/', ' ', $value ) ) ) ) ) : array();
	}

	/**
	 * Whether an input is a site search box with autocomplete.
	 *
	 * @param \WP_HTML_Tag_Processor $tag Input tag.
	 */
	private static function is_autocomplete_search( \WP_HTML_Tag_Processor $tag ): bool {
		$search = 'search' === strtolower( trim( (string) $tag->get_attribute( 'type' ) ) ) || 's' === $tag->get_attribute( 'name' );
		return $search && ( ! in_array( strtolower( trim( (string) $tag->get_attribute( 'aria-autocomplete' ) ) ), array( '', 'none' ), true ) || 'combobox' === strtolower( trim( (string) $tag->get_attribute( 'role' ) ) ) || null !== $tag->get_attribute( 'list' ) );
	}

	/**
	 * Whether a page names an author or a publish date, in meta tags or in JSON-LD. Null when none loaded.
	 *
	 * @param array<int, array{status:int,body:string,headers:array<string,string>}|null> $pages Pages, null for one that didn't load.
	 */
	private static function has_editorial_metadata( array $pages ): ?bool {
		$meta = self::page_has_tag(
			$pages,
			array( 'META' ),
			static fn( \WP_HTML_Tag_Processor $tag ): bool => in_array( strtolower( trim( (string) $tag->get_attribute( 'property' ) ) ), array( 'article:published_time', 'article:author' ), true ) || 'author' === strtolower( trim( (string) $tag->get_attribute( 'name' ) ) )
		);
		if ( true !== $meta ) {
			foreach ( array_filter( $pages ) as $page ) {
				foreach ( JsonLd::items( $page['body'], 'Article', 'BlogPosting', 'NewsArticle', 'WebPage', 'ItemPage', 'CollectionPage' ) as $item ) {
					if ( ! empty( $item['datePublished'] ) || ! empty( $item['author'] ) ) {
						return true;
					}
				}
			}
		}
		return $meta;
	}

	/**
	 * True when either result is true, null when neither is but one couldn't be checked.
	 *
	 * @param bool|null $first  First result.
	 * @param bool|null $second Second result.
	 */
	private static function either( ?bool $first, ?bool $second ): ?bool {
		if ( true === $first || true === $second ) {
			return true;
		}
		return null === $first || null === $second ? null : false;
	}

	/**
	 * Whether a document is an MCP server card: it names the server and says where to connect.
	 *
	 * @param array<mixed> $document Decoded document.
	 */
	private static function is_mcp_server_card( array $document ): bool {
		$named = ! empty( $document['name'] ) || ! empty( $document['serverInfo']['name'] );
		return $named && ( ! empty( $document['endpoint'] ) || ! empty( $document['url'] ) || ! empty( $document['remotes'] ) || ! empty( $document['transport'] ) );
	}

	/**
	 * The product page, or the home page without a product, asked for as Markdown.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @return array{status:int,body:string,headers:array<string,string>}|null
	 */
	private static function markdown_answer( StoreResponses $responses ): ?array {
		$product = $responses->product();
		return $responses->get_as_markdown( null === $product ? '/' : (string) $product->get_permalink() );
	}

	/**
	 * Whether a response is Markdown. A page a theme answers with for any address doesn't count.
	 *
	 * @param array{status:int,body:string,headers:array<string,string>}|null $response Response, null when it couldn't be loaded.
	 */
	private static function is_markdown( ?array $response ): ?bool {
		if ( null === $response || StoreOutputChecks::blocked( $response['status'] ) ) {
			return null;
		}
		$type = $response['headers']['content-type'] ?? '';
		return 200 === $response['status'] && '' !== trim( $response['body'] ) && ! str_starts_with( ltrim( $response['body'] ), '<' )
			&& ( 0 === stripos( $type, 'text/markdown' ) || 0 === stripos( $type, 'text/plain' ) );
	}

	/**
	 * Whether the Markdown version of the page starts with a frontmatter block. Null when it couldn't be loaded.
	 *
	 * @param array{status:int,body:string,headers:array<string,string>}|null $response Markdown answer.
	 */
	private static function has_frontmatter( ?array $response ): ?bool {
		$markdown = self::is_markdown( $response );
		if ( true !== $markdown || null === $response ) {
			return $markdown;
		}
		return 1 === preg_match( '/\A\s*---\r?\n.*?\r?\n---\r?\n/s', $response['body'] );
	}

	/**
	 * Whether a path answers with a JSON document that passes a test.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param string         $path      Path from the host root.
	 * @param callable       $test      Test on the decoded document.
	 */
	private static function document_served( StoreResponses $responses, string $path, callable $test ): ?bool {
		$response = $responses->get( StoreOutputChecks::host_root( $path ) );
		if ( null === $response || StoreOutputChecks::blocked( $response['status'] ) ) {
			return null;
		}
		$document = 200 === $response['status'] ? json_decode( $response['body'], true ) : null;
		return is_array( $document ) && $test( $document );
	}

	/**
	 * Whether a path answers with a text file. A theme that answers every URL with a page, or a host
	 * that answers unknown paths with a 200 JSON or HTML error, has no such file.
	 *
	 * @param StoreResponses $responses Store responses.
	 * @param string         $path      Path from the host root.
	 */
	private static function file_served( StoreResponses $responses, string $path ): ?bool {
		$response = $responses->get( StoreOutputChecks::host_root( $path ) );
		if ( null === $response || StoreOutputChecks::blocked( $response['status'] ) ) {
			return null;
		}
		$body = ltrim( $response['body'], " \t\r\n\xEF\xBB\xBF" );
		return 200 === $response['status'] && '' !== $body && '<' !== $body[0]
			&& 0 !== stripos( $response['headers']['content-type'] ?? '', 'text/html' )
			&& null === json_decode( $body );
	}

	/**
	 * Whether a page's JSON-LD has a property on an item that describes the store.
	 *
	 * @param array{status:int,body:string,headers:array<string,string>}|null $page     Page, null when it didn't load.
	 * @param string                                                          $property Property name.
	 */
	private static function json_ld_has( ?array $page, string $property ): ?bool {
		if ( null === $page ) {
			return null;
		}
		$types = array_merge( StoreOutputChecks::ORGANIZATION_TYPES, array( 'WebSite', 'Person' ) );
		foreach ( JsonLd::items( $page['body'], ...$types ) as $item ) {
			if ( ! empty( $item[ $property ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether a page's WebSite JSON-LD offers a SearchAction, on its own or in a list of actions.
	 *
	 * @param array{status:int,body:string,headers:array<string,string>}|null $page Page, null when it didn't load.
	 */
	private static function has_search_action( ?array $page ): ?bool {
		if ( null === $page ) {
			return null;
		}
		foreach ( JsonLd::items( $page['body'], 'WebSite' ) as $item ) {
			$actions = $item['potentialAction'] ?? array();
			$actions = is_array( $actions ) && isset( $actions['@type'] ) ? array( $actions ) : (array) $actions;
			foreach ( $actions as $action ) {
				if ( is_array( $action ) && in_array( 'SearchAction', preg_replace( '#^.*[/:]#', '', (array) ( $action['@type'] ?? array() ) ), true ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Whether the page's Link header points to the API catalog or llms.txt.
	 *
	 * @param array{status:int,body:string,headers:array<string,string>}|null $page Page, null when it didn't load.
	 */
	private static function links_agent_resources( ?array $page ): ?bool {
		if ( null === $page ) {
			return null;
		}
		// Each value is <target> followed by ;-separated parameters; rel holds space-separated tokens.
		preg_match_all( '/<([^>]*)>((?:[^,"<]|"[^"]*")*)/', $page['headers']['link'] ?? '', $values, PREG_SET_ORDER );
		foreach ( $values as $value ) {
			if ( '/llms.txt' === self::link_target_path( trim( $value[1] ) ) ) {
				return true;
			}
			// Parameters are read left to right, so a quoted value is taken whole, ; and rel= in it included.
			preg_match_all( '/;\s*([^=;\s]+)\s*(?:=\s*(?:"([^"]*)"|([^;]*)))?/', $value[2], $params, PREG_SET_ORDER );
			foreach ( $params as $param ) {
				if ( 'rel' === strtolower( $param[1] ) && in_array( 'api-catalog', self::tokens( ( $param[2] ?? '' ) . ' ' . ( $param[3] ?? '' ) ), true ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Path of a Link target resolved against the home page. `llms.txt` and `./llms.txt` there are `/llms.txt`.
	 *
	 * @param string $target Target from the Link header.
	 */
	private static function link_target_path( string $target ): string {
		while ( str_starts_with( $target, './' ) ) {
			$target = substr( $target, 2 );
		}
		$path = wp_parse_url( \WP_Http::make_absolute_url( $target, home_url( '/' ) ), PHP_URL_PATH );
		return is_string( $path ) ? $path : '';
	}
}
