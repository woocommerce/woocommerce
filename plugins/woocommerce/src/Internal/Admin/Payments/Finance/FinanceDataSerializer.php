<?php
/**
 * Finance data serializer.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\Payments\Finance\V1\Balance;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataPage;
use Automattic\WooCommerce\Admin\Payments\Finance\V1\Payout;

defined( 'ABSPATH' ) || exit;

/**
 * Turns finance data value objects into the arrays returned by the REST API.
 *
 * @internal
 */
class FinanceDataSerializer {

	/**
	 * Serialize a page of items into the REST envelope.
	 *
	 * @param FinanceDataPage $page           The page.
	 * @param int             $schema_version The data-model version of the items.
	 * @return array
	 * @throws \InvalidArgumentException When an item is not a Balance or a Payout.
	 *
	 * @since 11.2.0
	 */
	public function serialize_page( FinanceDataPage $page, int $schema_version ): array {
		$items = array();
		foreach ( $page->get_items() as $item ) {
			$items[] = $this->serialize_item( $item );
		}

		return array(
			'schema_version' => $schema_version,
			'items'          => $items,
			'has_more'       => $page->has_more(),
			'next_cursor'    => $page->get_next_cursor(),
			'prev_cursor'    => $page->get_prev_cursor(),
		);
	}

	/**
	 * Serialize a balance.
	 *
	 * @param Balance $balance The balance.
	 * @return array
	 *
	 * @since 11.2.0
	 */
	public function serialize_balance( Balance $balance ): array {
		$link = $balance->get_payout_link();

		$payout_link = null;
		if ( null !== $link ) {
			$payout_link = array(
				'title' => $link->get_title(),
				'url'   => $link->get_url(),
			);
		}

		return array(
			'provider_id'      => $balance->get_gateway_id(),
			'currency'         => $balance->get_currency(),
			'amount'           => $balance->get_amount(),
			'available_amount' => $balance->get_available_amount(),
			'payout_link'      => $payout_link,
		);
	}

	/**
	 * Serialize a payout.
	 *
	 * @param Payout $payout The payout.
	 * @return array
	 *
	 * @since 11.2.0
	 */
	public function serialize_payout( Payout $payout ): array {
		$date_expected = $payout->get_date_expected();

		return array(
			'provider_id'     => $payout->get_gateway_id(),
			'id'              => $payout->get_id(),
			'currency'        => $payout->get_currency(),
			'amount'          => $payout->get_amount(),
			'bank_account'    => $payout->get_bank_account(),
			'date_initiated'  => self::format_date( $payout->get_date_initiated() ),
			'date_expected'   => null === $date_expected ? null : self::format_date( $date_expected ),
			'status'          => $payout->get_status(),
			'provider_status' => $payout->get_provider_status(),
		);
	}

	/**
	 * Serialize one page item.
	 *
	 * @param object $item The item.
	 * @return array
	 * @throws \InvalidArgumentException When the item is not a Balance or a Payout.
	 */
	private function serialize_item( object $item ): array {
		if ( $item instanceof Balance ) {
			return $this->serialize_balance( $item );
		}

		if ( $item instanceof Payout ) {
			return $this->serialize_payout( $item );
		}

		throw new \InvalidArgumentException( 'Finance data page items must be Balance or Payout objects.' );
	}

	/**
	 * Format a date as an RFC 3339 date-time in UTC.
	 *
	 * @param \DateTimeInterface $date The date, in any timezone.
	 * @return string
	 */
	private static function format_date( \DateTimeInterface $date ): string {
		return gmdate( \DateTimeInterface::ATOM, $date->getTimestamp() );
	}
}
