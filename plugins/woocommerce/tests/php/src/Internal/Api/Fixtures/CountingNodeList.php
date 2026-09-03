<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api\Fixtures;

use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\DocumentNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\FragmentDefinitionNode;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\NodeList;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\SelectionSetNode;

/**
 * NodeList that counts how many times it is iterated.
 *
 * Tests swap it in for the `selections` of fragment selection sets to assert
 * how many times a walk over the document visits each fragment, however often
 * the fragment is spread.
 */
final class CountingNodeList extends NodeList {
	/**
	 * Number of iterations started over any CountingNodeList since the last reset().
	 *
	 * @var int
	 */
	public static int $iterations = 0;

	/**
	 * Reset the iteration counter.
	 */
	public static function reset(): void {
		self::$iterations = 0;
	}

	/**
	 * Replace the selections of a selection set with a counting copy.
	 *
	 * @param SelectionSetNode $selection_set The selection set to instrument.
	 */
	public static function instrument( SelectionSetNode $selection_set ): void {
		$selection_set->selections = new self( iterator_to_array( $selection_set->selections ) );
	}

	/**
	 * Instrument the selection set of every named fragment in a document.
	 *
	 * @param DocumentNode $document The parsed document.
	 * @return int The number of fragments instrumented.
	 */
	public static function instrument_fragments( DocumentNode $document ): int {
		$count = 0;
		foreach ( $document->definitions as $definition ) {
			if ( $definition instanceof FragmentDefinitionNode ) {
				self::instrument( $definition->selectionSet );
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Count the iteration, then iterate as usual.
	 */
	public function getIterator(): \Traversable {
		++self::$iterations;
		return parent::getIterator();
	}
}
