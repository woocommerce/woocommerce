<?php declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\Utils;

use Exception;

/**
 * Important: This class is a direct copy of the Block_Delimiter class in the WordPress.com.
 * Some linting rules have been disabled to allow for the copy and make future migration easier.
 *
 * Class for efficiently working with block structure.
 *
 * This class follows design values of the HTML API:
 *  - minimize allocations and strive for zero memory overhead
 *  - make costs explicit; pay only for what you need
 *  - follow a streaming, re-entrant design for pausing and aborting
 *
 * For usage, jump straight to {@see static::next_delimiter}.
 */
// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps, Squiz.Commenting.ClassComment.Missing
class Block_Delimiter {
	/**
	 * Indicates if the last operation failed, otherwise
	 * will be `null` for success.
	 *
	 * @var string|null
	 */
	private static ?string $last_error = self::UNINITIALIZED;

	/**
	 * Indicates failures from decoding JSON attributes.
	 *
	 * @var int
	 */
	private int $last_json_error = JSON_ERROR_NONE;

	/**
	 * Holds a reference to the original source text from which to
	 * extract the parsed spans of the delimiter.
	 *
	 * @var string
	 */
	private string $source_text;

	/**
	 * Byte offset into source text where entire delimiter begins.
	 *
	 * @var int
	 */
	private int $delimiter_at;

	/**
	 * Byte length of full span of delimiter.
	 *
	 * @var int
	 */
	private int $delimiter_length;

	/**
	 * Byte offset where namespace span begins.
	 *
	 * @var int
	 */
	private int $namespace_at;

	/**
	 * Byte length of namespace span, or `0` if implicitly in the "core" namespace.
	 *
	 * @var int
	 */
	private int $namespace_length;

	/**
	 * Byte offset where block name span begins.
	 *
	 * @var int
	 */
	private int $name_at;

	/**
	 * Byte length of block name span.
	 *
	 * @var int
	 */
	private int $name_length;

	/**
	 * Whether the delimiter contains the block self-closing flag.
	 *
	 * This may be erroneous if present within a block closer,
	 * therefore the {@see self::has_void_flag} can be used by
	 * calling code to perform appropriate error-handling.
	 *
	 * @var bool
	 */
	private bool $has_void_flag = false;

	/**
	 * Byte offset where JSON attributes span begins.
	 *
	 * @var int
	 */
	private int $json_at;

	/**
	 * Byte length of JSON attributes span, or `0` if none are present.
	 *
	 * @var int
	 */
	private int $json_length;

	/**
	 * Indicates what kind of block comment delimiter this represents.
	 *
	 * One of:
	 *
	 *  - `static::OPENER` If the delimiter is opening a block.
	 *  - `static::CLOSER` If the delimiter is closing an open block.
	 *  - `static::VOID`   If the delimiter represents a void block with no inner content.
	 *
	 * If a parsed comment delimiter contains both the closing and the void
	 * flags then it will be interpreted as a void block to match the behavior
	 * of the official block parser, however, this is a mistake and probably
	 * the block ought to close an open block of the same name, if one is open.
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * Finds the next block delimiter in a text document and returns a parsed
	 * block delimiter info record if it parses, otherwise returns `null`.
	 *
	 * Block comment delimiters must be valid HTML comments and may contain JSON.
	 * This search does not determine, however, if the JSON is valid.
	 *
	 * Example delimiters:
	 *
	 *     `<!-- wp:paragraph {"dropCap": true} -->`
	 *     `<!-- wp:separator /-->`
	 *     `<!-- /wp:paragraph -->`
	 *
	 * In the case that a block comment delimiter contains both the void indicator and
	 * also the closing indicator, it will be treated as a void block.
	 *
	 * Example:
	 *
	 *     // Find all image block opening delimiters.
	 *     $at     = 0;
	 *     $end    = strlen( $html );
	 *     $images = array();
	 *     while ( $at < $end ) {
	 *         $delimiter = Block_Delimiter::next_delimiter( $html, $at, $next_at, $next_length );
	 *         if ( ! isset( $delimiter ) ) {
	 *             break;
	 *         }
	 *
	 *         if (
	 *             Block_Delimiter::OPENER === $delimiter->get_delimiter_type() &&
	 *             $delimiter->is_block_type( 'core/image' )
	 *         ) {
	 *             $images[] = $delimiter;
	 *         }
	 *
	 *         $at = $next_at + $next_length;
	 *     }
	 *
	 * @param string   $text                 Input document possibly containing block comment delimiters.
	 * @param int      $starting_byte_offset Where in the input document to begin searching.
	 * @param int|null $match_byte_offset    Optional. When provided, will be set to the byte offset in
	 *                                       the input document where the delimiter was found, if one
	 *                                       is found, otherwise not set.
	 * @param int|null $match_byte_length    Optional. When provided, will be set to the byte length of
	 *                                       the matched delimiter if one is found, otherwise not set.
	 * @return Block_Delimiter|null Parsed block delimiter info record if found, otherwise `null`.
	 */
	public static function next_delimiter( string $text, int $starting_byte_offset, ?int &$match_byte_offset = null, ?int &$match_byte_length = null ): ?Block_Delimiter {
		$end                = strlen( $text );
		$at                 = $starting_byte_offset;
		$delimiter          = null;
		static::$last_error = null;

		$close_html_comment = function ( $comment_starting_at ) use ( $text, &$at, $end ) {
			// Find span-of-dashes comments which look like `<!----->`.
			$span_of_dashes = strspn( $text, '-', $comment_starting_at + 2 );
			if (
				$comment_starting_at + 2 + $span_of_dashes < $end &&
				'>' === $text[ $comment_starting_at + 2 + $span_of_dashes ]
			) {
				$at = $comment_starting_at + $span_of_dashes + 1;
				return;
			}

			// Otherwise, there are other characters inside the comment, find the first `-->` or `--!>`.
			$now_at = $comment_starting_at + 4;
			while ( $now_at < $end ) {
				$dashes_at = strpos( $text, '--', $now_at );
				if ( false === $dashes_at ) {
					static::$last_error = self::INCOMPLETE_INPUT;
					$at                 = $end;
					return;
				}

				$closer_must_be_at = $dashes_at + 2 + strspn( $text, '-', $dashes_at + 2 );
				if ( $closer_must_be_at < $end && '!' === $text[ $closer_must_be_at ] ) {
					++$closer_must_be_at;
				}

				if ( $closer_must_be_at < $end && '>' === $text[ $closer_must_be_at ] ) {
					$at = $closer_must_be_at + 1;
					return;
				}

				++$now_at;
			}
		};

		while ( $at < $end ) {
			/*
			 * Find the next possible opening.
			 *
			 * This follows the behavior in the official block parser, which treats a post
			 * as a list of blocks with nested HTML. If HTML comment syntax appears within
			 * an HTML attribute value, SCRIPT or STYLE element, or in other select places,
			 * which it can do inside of HTML, then the block parsing may break.
			 *
			 * For a more robust parse scan through the document with the HTML API. In
			 * practice, this has not been a problem in the entire history of blocks.
			 */
			$comment_opening_at = strpos( $text, '<!--', $at );
			if ( false === $comment_opening_at ) {
				return null;
			}

			$opening_whitespace_at     = $comment_opening_at + 4;
			$opening_whitespace_length = strspn( $text, " \t\f\r\n", $opening_whitespace_at );
			if ( 0 === $opening_whitespace_length ) {
				$close_html_comment( $comment_opening_at );
				continue;
			}

			$wp_prefix_at = $opening_whitespace_at + $opening_whitespace_length;
			if ( $wp_prefix_at >= $end ) {
				static::$last_error = self::INCOMPLETE_INPUT;
				return null;
			}

			$has_closer = false;
			if ( '/' === $text[ $wp_prefix_at ] ) {
				$has_closer = true;
				++$wp_prefix_at;
			}

			if ( 0 !== substr_compare( $text, 'wp:', $wp_prefix_at, 3 ) ) {
				$close_html_comment( $comment_opening_at );
				continue;
			}

			$namespace_at = $wp_prefix_at + 3;
			if ( $namespace_at >= $end ) {
				static::$last_error = self::INCOMPLETE_INPUT;
				return null;
			}

			$start_of_namespace = $text[ $namespace_at ];

			// The namespace must start with a-z.
			if ( 'a' > $start_of_namespace || 'z' < $start_of_namespace ) {
				$close_html_comment( $comment_opening_at );
				continue;
			}

			$namespace_length = 1 + strspn( $text, 'abcdefghijklmnopqrstuvwxyz0123456789-_', $namespace_at + 1 );
			$separator_at     = $namespace_at + $namespace_length;
			if ( $separator_at >= $end ) {
				static::$last_error = self::INCOMPLETE_INPUT;
				return null;
			}

			$has_separator = '/' === $text[ $separator_at ];
			if ( $has_separator ) {
				$name_at       = $separator_at + 1;
				$start_of_name = $text[ $name_at ];
				if ( 'a' > $start_of_name || 'z' < $start_of_name ) {
					$close_html_comment( $comment_opening_at );
					continue;
				}

				$name_length = 1 + strspn( $text, 'abcdefghijklmnopqrstuvwxyz0123456789-_', $name_at + 1 );
			} else {
				$name_at          = $namespace_at;
				$name_length      = $namespace_length;
				$namespace_length = 0;
			}

			$after_name_whitespace_at     = $name_at + $name_length;
			$after_name_whitespace_length = strspn( $text, " \t\f\r\n", $after_name_whitespace_at );
			if ( 0 === $after_name_whitespace_length ) {
				$close_html_comment( $comment_opening_at );
				continue;
			}

			$json_at = $after_name_whitespace_at + $after_name_whitespace_length;
			if ( $json_at >= $end ) {
				static::$last_error = self::INCOMPLETE_INPUT;
				return null;
			}
			$has_json    = '{' === $text[ $json_at ];
			$json_length = 0;

			/*
			 * For the final span of the delimiter it's most efficient to find the end
			 * of the HTML comment and work backwards. This prevents complicated parsing
			 * inside the JSON span, which cannot contain the HTML comment terminator.
			 *
			 * This also matches the behavior in the official block parser, though it
			 * allows for matching invalid JSON content.
			 */
			$comment_closing_at = strpos( $text, '-->', $json_at );
			if ( false === $comment_closing_at ) {
				static::$last_error = self::INCOMPLETE_INPUT;
				return null;
			}

			/*
			 * It looks like this logic leaves an error in here, when the position
			 * overlaps the JSON or block name. However, for neither of those is it
			 * possible to parse a valid block if that last overlapping character
			 * is the void flag. This, therefore, will be valid regardless of how
			 * the rest of the comment delimiter is written.
			 */
			if ( '/' === $text[ $comment_closing_at - 1 ] ) {
				$has_void_flag    = true;
				$void_flag_length = 1;
			} else {
				$has_void_flag    = false;
				$void_flag_length = 0;
			}

			/*
			 * If there's no JSON, then the span of text after the name
			 * until the comment closing must be completely whitespace.
			 */
			if ( ! $has_json ) {
				$max_whitespace_length = $comment_closing_at - $json_at - $void_flag_length;

				// This shouldn't be possible, but it can't be allowed regardless.
				if ( $max_whitespace_length < 0 ) {
					$close_html_comment( $comment_opening_at );
					continue;
				}

				$closing_whitespace_length = strspn( $text, " \t\f\r\n", $json_at, $comment_closing_at - $json_at - $void_flag_length );
				if ( 0 === $after_name_whitespace_length + $closing_whitespace_length ) {
					$close_html_comment( $comment_opening_at );
					continue;
				}

				// This must be a block delimiter!
				$delimiter = new static();
				break;
			}

			// There's no JSON, so attempt to find its boundary.
			$after_json_whitespace_length = 0;
			for ( $char_at = $comment_closing_at - $void_flag_length - 1; $char_at > $json_at; $char_at-- ) {
				$char = $text[ $char_at ];

				switch ( $char ) {
					case ' ':
					case "\t":
					case "\f":
					case "\r":
					case "\n":
						++$after_json_whitespace_length;
						continue 2;

					case '}':
						$json_length = $char_at - $json_at + 1;
						break 2;

					default:
						++$at;
						continue 3;
				}
			}

			if ( 0 === $json_length || 0 === $after_json_whitespace_length ) {
				$close_html_comment( $comment_opening_at );
				continue;
			}

			// This must be a block delimiter!
			$delimiter = new static();
			break;
		}

		if ( null === $delimiter ) {
			return null;
		}

		$delimiter->source_text = $text;

		$delimiter->delimiter_at     = $comment_opening_at;
		$delimiter->delimiter_length = $comment_closing_at + 3 - $comment_opening_at;

		$delimiter->namespace_at     = $namespace_at;
		$delimiter->namespace_length = $namespace_length;

		$delimiter->name_at     = $name_at;
		$delimiter->name_length = $name_length;

		$delimiter->json_at     = $json_at;
		$delimiter->json_length = $json_length;

		$delimiter->type = $has_closer
			? static::CLOSER
			: ( $has_void_flag ? static::VOID : static::OPENER );

		$delimiter->has_void_flag = $has_void_flag;

		$match_byte_offset = $delimiter->delimiter_at;
		$match_byte_length = $delimiter->delimiter_length;

		return $delimiter;
	}

	/**
	 * Generator function to traverse block delimiters in a text and also
	 * yield the position information for the delimiter as it scans.
	 *
	 * Example:
	 *
	 *     foreach ( Block_Delimiter::scan_delimiters( $post_content ) as $where => $delimiter ) {
	 *         echo "Found a {$delimiter->allocate_and_return_block_type()} at {$where[0]} (length is {$where[1]} bytes)\n";
	 *     }
	 *
	 * @param string  $text             Document potentially containing blocks.
	 * @param ?string $freeform_blocks Optional. 'visit' to visit virtual block delimiters for freeform content.
	 * @return \Generator Visits each block delimiter and provides [ at, length ] => delimiter.
	 */
	public static function scan_delimiters( string $text, ?string $freeform_blocks = 'skip' ): \Generator {
		$at    = 0;
		$depth = 0;

		/*
		 * Although `phpcs` confidently asserts that `$match_at` and `$match_length`
		 * are undefined, it is not aware enough to realize that they are set by the
		 * call to `next_delimiter` and so it’s necessary to alter the code so it
		 * doesn’t get confused and reject valid code.
		 */
		$match_at     = 0;
		$match_length = 0;

		while ( null !== ( $delimiter = self::next_delimiter( $text, $at, $match_at, $match_length ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			// Handle top-level text as freeform blocks.
			if ( 0 === $depth && $match_at > $at && 'visit' === $freeform_blocks ) {
				list( $text_opener, $text_closer ) = static::freeform_pair( $text, $at, $match_at - $at );

				++$depth;
				yield [ $at, 0 ] => $text_opener;

				--$depth;
				yield [ $match_at, 0 ] => $text_closer;
			}

			$delimiter_type = $delimiter->get_delimiter_type();

			switch ( $delimiter_type ) {
				case static::OPENER:
				case static::VOID:
					++$depth;
					break;

				case static::CLOSER:
					--$depth;
					break;
			}

			yield [ $match_at, $match_length ] => $delimiter;

			if ( static::VOID === $delimiter_type ) {
				--$depth;
			}

			$at = $match_at + $match_length;
		}

		$end = strlen( $text );
		if ( 'visit' === $freeform_blocks && $at < $end ) {
			list( $text_opener, $text_closer ) = static::freeform_pair( $text, $at, $end - $at );

			++$depth;
			yield [ $at, 0 ] => $text_opener;

			--$depth;
			yield [ $end, 0 ] => $text_closer;
		}
	}

	/**
	 * Constructor function.
	 */
	private function __construct() {
		// This is not to be called from the outside.
	}

	/**
	 * Creates a pair of delimiters for freeform text content
	 * since there are no delimiters in a document for them.
	 *
	 * @param string $text   Source document.
	 * @param int    $at     Where the text region starts (byte offset).
	 * @param int    $length How long the text region spans (byte count).
	 * @return static[] Opening and closing block delimiters for the text region.
	 */
	private static function freeform_pair( string $text, int $at, int $length ): array {
		$opener                   = new static();
		$opener->source_text      = $text;
		$opener->delimiter_at     = $at;
		$opener->delimiter_length = 0;
		$opener->namespace_at     = $at;
		$opener->namespace_length = 0;
		$opener->name_at          = $at;
		$opener->name_length      = 0;
		$opener->json_at          = $at;
		$opener->json_length      = 0;
		$opener->type             = static::OPENER;
		$opener->has_void_flag    = false;

		$closer               = clone $opener;
		$end_at               = $at + $length;
		$closer->delimiter_at = $end_at;
		$closer->namespace_at = $end_at;
		$closer->name_at      = $end_at;
		$closer->json_at      = $end_at;
		$closer->type         = static::CLOSER;

		return [ $opener, $closer ];
	}

	/**
	 * Indicates if the last attempt to parse a block comment delimiter
	 * failed, if set, otherwise `null` if the last attempt succeeded.
	 *
	 * @return string|null
	 */
	public static function get_last_error() {
		return static::$last_error;
	}

	/**
	 * Indicates if the last attempt to parse a block’s JSON attributes failed.
	 *
	 * @see JSON_ERROR_NONE, JSON_ERROR_DEPTH, etc…
	 *
	 * @return int JSON_ERROR_ code from last attempt to parse block JSON attributes.
	 */
	public function get_last_json_error(): int {
		return $this->last_json_error;
	}

	/**
	 * Allocates a substring from the source text containing the delimiter
	 * and releases the reference to the source text.
	 *
	 * Use this function when the delimiter is holding on to the source
	 * text and preventing it from being freed by PHP. This function incurs
	 * a string allocation; if the source text will be retained anyway then
	 * there's no need to detach as that memory cannot be freed.
	 *
	 * This is a low-level function available for controlling the performance
	 * of sensitive hot-paths. You probably don't need this.
	 *
	 * Example:
	 *
	 *     function first_block( $html ) {
	 *         return Block_Delimiter::next_delimiter( $really_long_html, 0 );
	 *     }
	 *
	 *     $delimiter = first_block( $really_long_html_document );
	 *     // `$really_long_html_document` is still retained inside `$delimiter`, which could lead to a memory leak.
	 *
	 *     $delimiter->allocate_and_detach_from_source_text();
	 *     // `$really_long_html_document` is no longer referenced, and its memory may be freed or used for something else.
	 *
	 * @return void
	 */
	public function allocate_and_detach_from_source_text(): void {
		$this->source_text = substr( $this->source_text, $this->delimiter_at, $this->delimiter_length );

		$byte_delta = $this->delimiter_at;

		$this->delimiter_at -= $byte_delta;
		$this->namespace_at -= $byte_delta;
		$this->name_at      -= $byte_delta;
		$this->json_at      -= $byte_delta;
	}

	/**
	 * Returns the type of the block comment delimiter.
	 *
	 * One of:
	 *
	 *  - `static::OPENER`
	 *  - `static::CLOSER`
	 *  - `static::VOID`
	 *
	 * @return string type of the block comment delimiter.
	 */
	public function get_delimiter_type(): string {
		return $this->type;
	}

	/**
	 * Returns whether the delimiter contains the void flag.
	 *
	 * This should be avoided except in cases of handling errors with
	 * block closers containing the void flag. For normative use,
	 * {@see self::get_delimiter_type}.
	 *
	 * @return bool
	 */
	public function has_void_flag(): bool {
		return $this->has_void_flag;
	}

	/**
	 * Indicates if the block delimiter represents a block of the given type.
	 *
	 * Since the "core" namespace may be implicit, it's allowable to pass
	 * either the fully-qualified block type with namespace and block name
	 * as well as the shorthand version only containing the block name, if
	 * the desired block is in the "core" namespace.
	 *
	 * Example:
	 *
	 *     $is_core_paragraph = $delimiter->is_block_type( 'paragraph' );
	 *     $is_core_paragraph = $delimiter->is_block_type( 'core/paragraph' );
	 *     $is_formula        = $delimiter->is_block_type( 'math-block/formula' );
	 *
	 * @param string $block_type Block type name for the desired block.
	 *                           E.g. "paragraph", "core/paragraph", "math-blocks/formula".
	 * @return bool Whether this delimiter represents a block of the given type.
	 */
	public function is_block_type( string $block_type ): bool {
		// This is a core/freeform text block, it’s special.
		if ( 0 === $this->name_length ) {
			return 'core/freeform' === $block_type || 'freeform' === $block_type;
		}

		$slash_at = strpos( $block_type, '/' );
		if ( false === $slash_at ) {
			$namespace  = 'core';
			$block_name = $block_type;
		} else {
			$namespace  = substr( $block_type, 0, $slash_at );
			$block_name = substr( $block_type, $slash_at + 1 );
		}

		// Only the 'core' namespace is allowed to be omitted.
		if ( 0 === $this->namespace_length && 'core' !== $namespace ) {
			return false;
		}

		// If given an explicit namespace, they must match.
		if (
			0 !== $this->namespace_length && (
				strlen( $namespace ) !== $this->namespace_length ||
				0 !== substr_compare( $this->source_text, $namespace, $this->namespace_at, $this->namespace_length )
			)
		) {
			return false;
		}

		// The block name must match.
		return (
			strlen( $block_name ) === $this->name_length &&
			0 === substr_compare( $this->source_text, $block_name, $this->name_at, $this->name_length )
		);
	}

	/**
	 * Allocates a substring for the block type and returns the
	 * fully-qualified name, including the namespace.
	 *
	 * This function allocates a substring for the given block type. This
	 * allocation will be small and likely fine in most cases, but it's
	 * preferable to call {@link static::is_block_type} if only needing
	 * to know whether the delimiter is for a given block type, as that
	 * function is more efficient for this purpose and avoids the allocation.
	 *
	 * Example:
	 *
	 *     'core/paragraph' = $delimiter->allocate_and_return_block_type();
	 *
	 * @return string Fully-qualified block namespace and type, e.g. "core/paragraph".
	 */
	public function allocate_and_return_block_type(): string {
		// This is a core/freeform text block, it’s special.
		if ( 0 === $this->name_length ) {
			return 'core/freeform';
		}

		// This is implicitly in the "core" namespace.
		if ( 0 === $this->namespace_length ) {
			$block_name = substr( $this->source_text, $this->name_at, $this->name_length );
			return "core/{$block_name}";
		}

		return substr( $this->source_text, $this->namespace_at, $this->namespace_length + $this->name_length + 1 );
	}

	/**
	 * Returns a lazy wrapper around the block attributes, which can be used
	 * for efficiently interacting with the JSON attributes.
	 *
	 * @throws Exception This function is not yet implemented.
	 *
	 * @see \JsonStreamingParser\Parser
	 *
	 * @return void
	 */
	public function get_attributes(): void {
		throw new Exception( 'Lazy attribute parsing not yet supported' );
	}

	/**
	 * Attempts to parse and return the entire JSON attributes from the delimiter,
	 * allocating memory and processing the JSON span in the process.
	 *
	 * This does not return any parsed attributes for a closing block delimiter
	 * even if there is a span of JSON content; this JSON is a parsing error.
	 *
	 * Consider calling {@link static::get_attributes} instead if it's not
	 * necessary to read all the attributes at the same time, as that provides
	 * a more efficient mechanism for typical use cases.
	 *
	 * Since the JSON span inside the comment delimiter may not be valid JSON,
	 * this function will return `null` if it cannot parse the span and set the
	 * {@see static::get_last_json_error} to the appropriate JSON_ERROR_ constant.
	 *
	 * If the delimiter contains no JSON span, it will also return `null`,
	 * but the last error will be set to {@see JSON_ERROR_NONE}.
	 *
	 * Example:
	 *
	 *     $delimiter = Block_Delimiter::next_delimiter( '<!-- wp:image {"url": "https://wordpress.org/favicon.ico"} -->', 0 );
	 *     $memory_hungry_and_slow_attributes = $delimiter->allocate_and_return_parsed_attributes();
	 *     $memory_hungry_and_slow_attributes === array( 'url' => 'https://wordpress.org/favicon.ico' );
	 *
	 *     $delimiter      = Block_Delimiter::next_delimiter( '<!-- /wp:image {"url": "https://wordpress.org/favicon.ico"} -->', 0 );
	 *     null            = $delimiter->allocate_and_return_parsed_attributes();
	 *     JSON_ERROR_NONE = $delimiter->get_last_json_error();
	 *
	 *     $delimiter = Block_Delimiter::next_delimiter( '<!-- wp:separator {} /-->', 0 );
	 *     array()    === $delimiter->allocate_and_return_parsed_attributes();
	 *
	 *     $delimiter = Block_Delimiter::next_delimiter( '<!-- wp:separator /-->', 0 );
	 *     null       = $delimiter->allocate_and_return_parsed_attributes();
	 *
	 *     $delimiter           = Block_Delimiter::next_delimiter( '<!-- wp:image {"url} -->', 0 );
	 *     null                 = $delimiter->allocate_and_return_parsed_attributes();
	 *     JSON_ERROR_CTRL_CHAR = $delimiter->get_last_json_error();
	 *
	 * @return array|null Parsed JSON attributes, if present and valid, otherwise `null`.
	 */
	public function allocate_and_return_parsed_attributes(): ?array {
		$this->last_json_error = JSON_ERROR_NONE;

		if ( static::CLOSER === $this->type ) {
			return null;
		}

		if ( 0 === $this->json_length ) {
			return null;
		}

		$json_span = substr( $this->source_text, $this->json_at, $this->json_length );
		$parsed    = json_decode( $json_span, null, 512, JSON_OBJECT_AS_ARRAY | JSON_INVALID_UTF8_SUBSTITUTE );

		$last_error            = json_last_error();
		$this->last_json_error = $last_error;

		return ( JSON_ERROR_NONE === $last_error && is_array( $parsed ) )
			? $parsed
			: null;
	}

	// Debugging methods not meant for production use.

	/**
	 * Prints a debugging message showing the structure of the parsed delimiter.
	 *
	 * This is not meant to be used in production!
	 *
	 * @internal
	 */
	public function debug_print_structure(): void {
		$c = ( ! defined( 'STDOUT' ) || posix_isatty( STDOUT ) )
			? function ( $color = null ) { return $color; } // phpcs:ignore
			: function ( $color ) { return ''; }; // phpcs:ignore

		if ( $this->is_block_type( 'core/freeform' ) ) {
			$closer = static::CLOSER === $this->get_delimiter_type() ? '/' : '';
			echo "{$c( "\e[90m" )}<!-- "; // phpcs:ignore
			echo "{$c( "\e[0;31m" )}{$closer}"; // phpcs:ignore
			echo "{$c("\e[90m" )}wp:"; // phpcs:ignore
			echo "{$c( "\e[0;34m" )}freeform"; // phpcs:ignore
			echo "{$c( "\e[0;36m" )} {$c("\e[90m")}-->\n"; // phpcs:ignore
			return;
		}

		$namespace  = substr( $this->source_text, $this->namespace_at, $this->namespace_length );
		$slash      = 0 === $this->namespace_length ? '' : '/';
		$block_name = substr( $this->source_text, $this->name_at, $this->name_length );
		$closer     = static::CLOSER === $this->type ? '/' : '';
		$json       = substr( $this->source_text, $this->json_at, $this->json_length );

		$opener_whitespace_at     = $this->delimiter_at + 4;
		$opener_whitespace_length = $this->namespace_at - 3 - $opener_whitespace_at - ( static::CLOSER === $this->type ? 1 : 0 );

		$after_name_whitespace_at     = $this->name_at + $this->name_length;
		$after_name_whitespace_length = $this->json_at - $after_name_whitespace_at;

		$closing_whitespace_at     = $this->json_at + $this->json_length;
		$closing_whitespace_length = $this->delimiter_at + $this->delimiter_length - 3 - $closing_whitespace_at;

		if ( '/' === $this->source_text[ $this->delimiter_at + $this->delimiter_length - 4 ] ) {
			$void_flag = '/';
			--$closing_whitespace_length;
		} else {
			$void_flag = '';
		}

		$w = function ( $whitespace ) use ( $c ) {
			return $c( "\e[2;90m" ) . str_replace( array( ' ', "\t", "\f", "\r", "\n" ), array( '␣', '␉', '␌', '␍', '␤' ), $whitespace );
		};

		echo "{$c( "\e[90m" )}<!--"; // phpcs:ignore
		echo $w( substr( $this->source_text, $opener_whitespace_at, $opener_whitespace_length ) ); // phpcs:ignore
		echo "{$c( "\e[0;31m" )}{$closer}"; // phpcs:ignore
		echo "{$c("\e[90m" )}wp:{$c( "\e[2;34m" )}{$namespace}"; // phpcs:ignore
		echo "{$c( "\e[2;90m" )}{$slash}"; // phpcs:ignore
		echo "{$c( "\e[0;34m" )}{$block_name}"; // phpcs:ignore
		echo $w( substr( $this->source_text, $after_name_whitespace_at, $after_name_whitespace_length ) ); // phpcs:ignore
		echo "{$c("\e[0;2;32m" )}{$json}"; // phpcs:ignore
		echo $w( substr( $this->source_text, $closing_whitespace_at, $closing_whitespace_length ) ); // phpcs:ignore
		echo "{$c( "\e[0;36m" )}{$void_flag}{$c("\e[90m")}-->\n"; // phpcs:ignore
	}

	// Constant declarations that would otherwise pollute the top of the class.

	/**
	 * Indicates that the block comment delimiter closes an open block.
	 */
	const CLOSER = 'closer';

	/**
	 * Indicates that the parser started parsing a block comment delimiter, but
	 * the input document ended before it could finish. The document was likely truncated.
	 */
	const INCOMPLETE_INPUT = 'incomplete-input';

	/**
	 * Indicates that the block comment delimiter opens a block.
	 */
	const OPENER = 'opener';

	/**
	 * Indicates that the parser has not yet attempted to parse a block comment delimiter.
	 */
	const UNINITIALIZED = 'uninitialized';

	/**
	 * Indicates that the block comment delimiter represents a void block
	 * with no inner content of any kind.
	 */
	const VOID = 'void';
}
