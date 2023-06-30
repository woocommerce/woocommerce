<?php

namespace WooCommerceDocs\Markdown;

/**
 * Class GutenbergComment
 *
 * @package WooCommerceDocs\Markdown
 */
final class GutenbergComment implements \Stringable {

	/**
	 * @var string
	 *
	 * Gutenberg block name.
	 */
	private $block_name;

	/**
	 * @var \Stringable|\Stringable[]|string
	 *
	 * Gutenberg block contents.
	 */
	private $contents;

	/**
	 * GutenbergComment constructor.
	 */
	public function __construct( string $block_name, $contents = '' ) {
		$this->block_name = $block_name;
		$this->setContents( $contents ?? '' );
	}

	public function get_block_name(): string {
		return $this->block_name;
	}

	/**
	 * @return \Stringable|\Stringable[]|string
	 */
	public function getContents( bool $as_string = true ) {
		if ( ! $as_string ) {
			return $this->contents;
		}

		return $this->getContentsAsString();
	}

	public function setContents( $contents ): self {
		$this->contents = $contents ?? ''; // @phpstan-ignore-line

		return $this;
	}

	public function __toString(): string {
		$result = '<!-- wp:' . $this->block_name . ' -->';

		if ( $this->contents !== '' ) {
			$result .= $this->getContentsAsString() . '<!-- /wp:' . $this->block_name . ' -->';
		}

		return $result;
	}

	private function getContentsAsString(): string {
		if ( \is_string( $this->contents ) ) {
			return $this->contents;
		}

		if ( \is_array( $this->contents ) ) {
			return \implode( '', $this->contents );
		}

		return (string) $this->contents;
	}
}
