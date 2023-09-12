<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

/**
 * Logger for block template modifications.
 */
class BlockTemplateLogger {
	/**
	 * Singleton instance.
	 *
	 * @var BlockTemplateLogger
	 */
	protected static $instance = null;

	/**
	 * Logger instance.
	 *
	 * @var \WC_Logger
	 */
	protected $logger = null;

	/**
	 * Get the singleton instance.
	 */
	public static function get_instance(): BlockTemplateLogger {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->logger = wc_get_logger();
	}

	/**
	 * Log an informational message.
	 *
	 * @param string $message Message to log.
	 * @param array  $info    Additional info to log.
	 */
	public function info( string $message, array $info = [] ) {
		$this->logger->info(
			$this->format_message( $message, $info ),
			[ 'source' => 'block_template' ]
		);
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Message to log.
	 * @param array  $info    Additional info to log.
	 */
	public function warning( string $message, array $info = [] ) {
		$this->logger->warning(
			$this->format_message( $message, $info ),
			[ 'source' => 'block_template' ]
		);
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Message to log.
	 * @param array  $info    Additional info to log.
	 */
	public function error( string $message, array $info = [] ) {
		$this->logger->error(
			$this->format_message( $message, $info ),
			[ 'source' => 'block_template' ]
		);
	}

	/**
	 * Format a message for logging.
	 *
	 * @param string $message Message to log.
	 * @param array  $info    Additional info to log.
	 */
	private function format_message( string $message, array $info = [] ): string {
		$formatted_message = sprintf(
			"%s\n%s",
			$message,
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			print_r( $this->format_info( $info ), true ),
		);

		return $formatted_message;
	}

	/**
	 * Format info for logging.
	 *
	 * @param array $info Info to log.
	 */
	private function format_info( array $info ): array {
		$formatted_info = $info;

		if ( isset( $info['exception'] ) && $info['exception'] instanceof \Exception ) {
			$formatted_info['exception'] = $this->format_exception( $info['exception'] );
		}

		if ( isset( $info['container'] ) ) {
			if ( $info['container'] instanceof BlockContainerInterface ) {
				$formatted_info['container'] = $this->format_block( $info['container'] );
			} elseif ( $info['container'] instanceof BlockTemplateInterface ) {
				$formatted_info['container'] = $this->format_template( $info['container'] );
			} elseif ( $info['container'] instanceof BlockInterface ) {
				$formatted_info['container'] = $this->format_block( $info['container'] );
			}
		}

		if ( isset( $info['block'] ) && $info['block'] instanceof BlockInterface ) {
			$formatted_info['block'] = $this->format_block( $info['block'] );
		}

		if ( isset( $info['template'] ) && $info['template'] instanceof BlockTemplateInterface ) {
			$formatted_info['template'] = $this->format_template( $info['template'] );
		}

		return $formatted_info;
	}

	/**
	 * Format an exception for logging.
	 *
	 * @param \Exception $exception Exception to format.
	 */
	private function format_exception( \Exception $exception ): array {
		return [
			'message' => $exception->getMessage(),
			'source'  => "{$exception->getFile()}: {$exception->getLine()}",
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			'trace'   => print_r( $this->format_exception_trace( $exception->getTrace() ), true ),
		];
	}

	/**
	 * Format an exception trace for logging.
	 *
	 * @param array $trace Exception trace to format.
	 */
	private function format_exception_trace( array $trace ): array {
		$formatted_trace = [];

		foreach ( $trace as $source ) {
			$formatted_trace[] = "{$source['file']}: {$source['line']}";
		}

		return $formatted_trace;
	}

	/**
	 * Format a block template for logging.
	 *
	 * @param BlockTemplateInterface $template Template to format.
	 */
	private function format_template( BlockTemplateInterface $template ): string {
		return "{$template->get_id()} (area: {$template->get_area()})";
	}

	/**
	 * Format a block for logging.
	 *
	 * @param BlockInterface $block Block to format.
	 */
	private function format_block( BlockInterface $block ): string {
		return "{$block->get_id()} (name: {$block->get_name()})";
	}
}
