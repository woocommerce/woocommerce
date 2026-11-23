<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonHelper;

defined( 'ABSPATH' ) || exit;

/**
 * The plain-text report a merchant downloads from Sales readiness and sends to support. It is built
 * from the stored report and the store's settings, without running a check, and holds only settings,
 * check results and products: no customer or order data, and no keys. Like the System Status Report,
 * its labels are in English, so support can read any store's report; what the page says stays translated.
 *
 * @since 11.4.0
 */
class SupportReport {

	/**
	 * Readiness service.
	 *
	 * @var AgentReadiness
	 */
	private AgentReadiness $agent_readiness;

	/**
	 * Store conditions.
	 *
	 * @var StoreConditions
	 */
	private StoreConditions $store_conditions;

	/**
	 * Background scan.
	 *
	 * @var ScheduledScan
	 */
	private ScheduledScan $scheduled_scan;

	/**
	 * UCP switch.
	 *
	 * @var UcpSwitch
	 */
	private UcpSwitch $ucp_switch;

	/**
	 * Rules file.
	 *
	 * @var RemoteRules
	 */
	private RemoteRules $remote_rules;

	/**
	 * Coming soon mode.
	 *
	 * @var ComingSoonHelper
	 */
	private ComingSoonHelper $coming_soon;

	/**
	 * Sales readiness, which decides which checks it shows.
	 *
	 * @var ReadinessCheckPage
	 */
	private ReadinessCheckPage $readiness_page;

	/**
	 * Initialize dependencies.
	 *
	 * @internal
	 *
	 * @param AgentReadiness     $agent_readiness  Readiness service.
	 * @param StoreConditions    $store_conditions Store conditions.
	 * @param ScheduledScan      $scheduled_scan   Background scan.
	 * @param UcpSwitch          $ucp_switch       UCP switch.
	 * @param RemoteRules        $remote_rules     Rules file.
	 * @param ComingSoonHelper   $coming_soon      Coming soon mode.
	 * @param ReadinessCheckPage $readiness_page   Sales readiness.
	 */
	final public function init( AgentReadiness $agent_readiness, StoreConditions $store_conditions, ScheduledScan $scheduled_scan, UcpSwitch $ucp_switch, RemoteRules $remote_rules, ComingSoonHelper $coming_soon, ReadinessCheckPage $readiness_page ): void {
		$this->agent_readiness  = $agent_readiness;
		$this->store_conditions = $store_conditions;
		$this->scheduled_scan   = $scheduled_scan;
		$this->ucp_switch       = $ucp_switch;
		$this->remote_rules     = $remote_rules;
		$this->coming_soon      = $coming_soon;
		$this->readiness_page   = $readiness_page;
	}

	/**
	 * File name for the download, like the System Status Report's: AISalesReadiness_<site host>_<date and time>.txt.
	 */
	public function filename(): string {
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		return sanitize_file_name( 'AISalesReadiness_' . ( '' !== $host ? $host : 'site' ) . '_' . gmdate( 'Y-m-d\TH-i-s' ) . '.txt' );
	}

	/**
	 * The report as text, one line per item.
	 */
	public function text(): string {
		// Pending before and after the report read, so a scan that finishes mid-read is not shown as Finished.
		$pending = $this->scheduled_scan->is_pending();
		$report  = $this->agent_readiness->get_cached_report();
		$pending = $pending || $this->scheduled_scan->is_pending();
		$live    = $this->store_conditions->all();
		$lines   = array_merge(
			$this->header( $report, $pending ),
			array( '' ),
			$this->shopping( $report ),
			array( '' ),
			$this->store_checks( $report, $live )
		);
		if ( null !== $report ) {
			$lines = array_merge( $lines, array( '' ), self::product_checks( $report ) );
		}
		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Site, versions, and when the last check ran and how it ended.
	 *
	 * @param array<string, mixed>|null $report  Stored report.
	 * @param bool                      $pending Whether a scan is pending around the report read.
	 * @return string[]
	 */
	private function header( ?array $report, bool $pending ): array {
		$rules = $this->remote_rules->version();
		if ( $pending && $this->scheduled_scan->is_stalled() ) {
			$state = 'Checks can’t start because scheduled actions aren’t running on your site. Your hosting provider can usually turn them back on.';
		} elseif ( $pending ) {
			$state = 'Checking your store';
		} else {
			$state = null === $report ? 'Not checked yet' : 'Finished';
		}
		$lines = array(
			'### AI sales readiness ###',
			'',
			'Site: ' . home_url(),
			'WooCommerce version: ' . (string) Constants::get_constant( 'WC_VERSION' ),
			'Rules version: ' . ( '' !== $rules ? $rules : 'Built into WooCommerce' ),
			'Last checked: ' . ( null === $report ? 'Not checked yet' : (string) ( $report['generated_at'] ?? '' ) ),
			'Check status: ' . $state,
		);
		if ( null !== $report && ! empty( $report['truncated'] ) ) {
			$lines[] = sprintf( 'Checked the %s products you changed most recently.', ProductScan::PRODUCT_LIMIT );
		}
		return $lines;
	}

	/**
	 * The UCP switch, store visibility and product visibility.
	 *
	 * @param array<string, mixed>|null $report Stored report.
	 * @return string[]
	 */
	private function shopping( ?array $report ): array {
		$statuses = array(
			UcpSwitch::STATUS_ACTIVE          => 'On',
			UcpSwitch::STATUS_NEEDS_ATTENTION => 'On, with required steps left',
			UcpSwitch::STATUS_OFF             => 'Off',
		);
		if ( $this->coming_soon->is_site_coming_soon() ) {
			$visibility = 'Coming soon mode (whole site)';
		} elseif ( $this->coming_soon->is_store_coming_soon() ) {
			$visibility = 'Coming soon mode (store pages)';
		} else {
			$visibility = 'Live';
		}
		$lines = array(
			'### AI shopping ###',
			'',
			'AI shopping: ' . ( $statuses[ $this->ucp_switch->status() ] ?? '' ),
			'Store visibility: ' . $visibility,
		);
		if ( null !== $report ) {
			$lines[] = 'Products checked: ' . (string) ( (int) ( $report['products'] ?? 0 ) );
			$lines[] = 'Hidden: ' . (string) ( (int) ( $report['hidden_from_catalog'] ?? 0 ) );
			$lines[] = 'Not supported: ' . (string) ( (int) ( $report['unsupported_types'] ?? 0 ) );
		}
		return $lines;
	}

	/**
	 * Every setup step, read now, then every scanned check from the report, with its status and what was found when it fails.
	 *
	 * @param array<string, mixed>|null                                                                                                     $report Stored report.
	 * @param array<int, array{id:string,ok:bool,blocks:string,level:string,name:string,label:string,why:string,fix:string,fix_url:string}> $live   Store conditions read now.
	 * @return string[]
	 */
	private function store_checks( ?array $report, array $live ): array {
		$scanned = array_slice( $this->agent_readiness->get_store_checks( $report, $live ), count( $live ) );
		$lines   = array( '### Setup steps ###', '' );
		foreach ( $live as $check ) {
			// The page shows the UCP switch, not a row, so this step takes the page's line for AI shopping being off.
			$found   = 'ucp_on' === $check['id'] ? ReadinessCheckPage::ucp_off_note() : ReadinessCheckPage::failing_text( $check );
			$lines[] = self::check_line( $check, $found );
			if ( 'payment_method' === $check['id'] ) {
				// A method can be on and still not take an order, like Cash on delivery for virtual products, so support needs the names.
				$methods = $this->store_conditions->enabled_payment_methods();
				$lines[] = 'Enabled payment methods: ' . ( array() !== $methods ? implode( ', ', $methods ) : 'None' );
			}
		}
		$lines[] = '';
		$lines[] = '### Store checks ###';
		$lines[] = '';
		if ( null === $report ) {
			$lines[] = 'Not checked yet';
			return $lines;
		}
		$shown = $this->readiness_page->shown_check_ids( $report, $live );
		if ( ReadinessCheckPage::none_ran( array_values( array_filter( $scanned, static fn( array $check ): bool => in_array( $check['id'], $shown, true ) ) ) ) ) {
			$copy    = ReadinessCheckPage::cant_check_copy( (bool) ( array_column( $live, 'ok', 'id' )['launched'] ?? true ) );
			$lines[] = $copy[0] . '. ' . $copy[1];
			$lines[] = '';
		}
		foreach ( $scanned as $check ) {
			$line = self::check_line( $check, ReadinessCheckPage::failing_text( $check ) );
			if ( ! in_array( $check['id'], $shown, true ) ) {
				$line .= ' (not shown on the page)';
			}
			$lines[] = $line;
		}
		return $lines;
	}

	/**
	 * One check: status, name and id, then what was found when it fails, where it was seen and what the store answered.
	 *
	 * @param array<string, mixed> $check Store check.
	 * @param string               $found What was found, shown when it fails.
	 */
	private static function check_line( array $check, string $found ): string {
		if ( ! empty( $check['pending'] ) ) {
			$status = 'Not available yet';
		} elseif ( null === $check['ok'] ) {
			$status = 'Couldn’t check';
		} else {
			$status = $check['ok'] ? '✔' : '❌ ' . trim( $found );
		}
		$line = sprintf( '%s (%s): %s', $check['name'], $check['id'], $status );
		if ( false === $check['ok'] && empty( $check['pending'] ) ) {
			if ( ! empty( $check['conflict'] ) ) {
				$line .= ' (likely conflict)';
			}
		}
		if ( '' !== ( $check['seen_at'] ?? '' ) ) {
			// Catalog search names the request it sent; a conflict names the page it was seen on.
			$line .= 'ucp_endpoints_live' === $check['id'] ? ' Request: POST ' . self::request_path( (string) $check['seen_at'] ) : ' Found on: ' . $check['seen_at'];
		}
		if ( '' !== ( $check['source'] ?? '' ) && true !== $check['ok'] ) {
			$line .= ' Store response: ' . $check['source'];
		}
		return $line;
	}

	/**
	 * A store URL without its scheme and host, such as /wp-json/wc/ucp/v1/catalog/search, keeping the query plain permalinks use.
	 *
	 * @param string $url Store URL.
	 */
	private static function request_path( string $url ): string {
		$path  = (string) wp_parse_url( $url, PHP_URL_PATH );
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );
		return ( '' !== $path ? $path : '/' ) . ( '' !== $query ? '?' . $query : '' );
	}

	/**
	 * Channel totals, then each product check with its count and example products, by ID and name.
	 *
	 * @param array<string, mixed> $report Stored report.
	 * @return string[]
	 */
	private static function product_checks( array $report ): array {
		$lines = array( '### Product checks ###', '' );
		foreach ( is_array( $report['channels'] ?? null ) ? $report['channels'] : array() as $id => $channel ) {
			if ( ! is_array( $channel ) ) {
				continue;
			}
			$lines[] = sprintf(
				'%1$s (%2$s): %3$s can be listed, %4$s can’t be listed, %5$s listed with missing details',
				(string) ( $channel['name'] ?? $id ),
				(string) $id,
				(string) ( (int) ( $channel['listable'] ?? 0 ) ),
				(string) ( (int) ( $channel['not_listable'] ?? 0 ) ),
				(string) ( (int) ( $channel['with_gaps'] ?? 0 ) )
			);
		}
		$lines[] = '';
		foreach ( is_array( $report['checks'] ?? null ) ? $report['checks'] : array() as $check ) {
			if ( ! is_array( $check ) || ! isset( $check['id'] ) ) {
				continue;
			}
			$count = (int) ( $check['count'] ?? 0 );
			$line  = sprintf( '%s (%s): %s', (string) ( $check['label'] ?? $check['id'] ), (string) $check['id'], 0 === $count ? '✔' : '❌' );
			if ( ReadinessCheckPage::hides_product_check( (string) $check['id'] ) ) {
				$line .= ' (not shown on the page)';
			}
			if ( $count > 0 ) {
				$ids   = array_map( 'intval', (array) ( $check['product_ids'] ?? array() ) );
				$line .= ' ' . sprintf( 1 === $count ? '%s product' : '%s products', $count );
				if ( array() !== $ids ) {
					_prime_post_caches( $ids, false, false );
					$named = array_map( static fn( int $id ): string => sprintf( '%d (%s)', $id, wp_strip_all_tags( get_the_title( $id ) ) ), $ids );
					$line .= '. Products: ' . implode( ', ', $named );
				}
			}
			$lines[] = $line;
		}
		return $lines;
	}
}
