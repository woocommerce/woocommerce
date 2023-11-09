<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplates;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockContainerInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;
use Automattic\WooCommerce\Admin\BlockTemplates\ContainerInterface;

/**
 * Logger for block template modifications.
 */
class BlockTemplateLogger {
	const BLOCK_ADDED                            = 'block_added';
	const BLOCK_REMOVED                          = 'block_removed';
	const BLOCK_MODIFIED                         = 'block_modified';
	const BLOCK_ADDED_TO_DETACHED_CONTAINER      = 'block_added_to_detached_container';
	const HIDE_CONDITION_ADDED                   = 'hide_condition_added';
	const HIDE_CONDITION_REMOVED                 = 'hide_condition_removed';
	const HIDE_CONDITION_ADDED_TO_DETACHED_BLOCK = 'hide_condition_added_to_detached_block';
	const ERROR_AFTER_BLOCK_ADDED                = 'error_after_block_added';
	const ERROR_AFTER_BLOCK_REMOVED              = 'error_after_block_removed';

	/**
	 * Event types.
	 *
	 * @var array
	 */
	public static $event_types = array(
		self::BLOCK_ADDED                            => array(
			'level'   => \WC_Log_Levels::DEBUG,
			'message' => 'Block added to template.',
		),
		self::BLOCK_REMOVED                          => array(
			'level'   => \WC_Log_Levels::NOTICE,
			'message' => 'Block removed from template.',
		),
		self::BLOCK_MODIFIED                         => array(
			'level'   => \WC_Log_Levels::NOTICE,
			'message' => 'Block modified in template.',
		),
		self::BLOCK_ADDED_TO_DETACHED_CONTAINER      => array(
			'level'   => \WC_Log_Levels::WARNING,
			'message' => 'Block added to detached container. Block will not be included in the template, since the container will not be included in the template.',
		),
		self::HIDE_CONDITION_ADDED                   => array(
			'level'   => \WC_Log_Levels::NOTICE,
			'message' => 'Hide condition added to block.',
		),
		self::HIDE_CONDITION_REMOVED                 => array(
			'level'   => \WC_Log_Levels::NOTICE,
			'message' => 'Hide condition removed from block.',
		),
		self::HIDE_CONDITION_ADDED_TO_DETACHED_BLOCK => array(
			'level'   => \WC_Log_Levels::WARNING,
			'message' => 'Hide condition added to block.',
		),
		self::ERROR_AFTER_BLOCK_ADDED                => array(
			'level'   => \WC_Log_Levels::WARNING,
			'message' => 'Error after block added to template.',
		),
		self::ERROR_AFTER_BLOCK_REMOVED              => array(
			'level'   => \WC_Log_Levels::WARNING,
			'message' => 'Error after block removed from template.',
		),
	);

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
	 * All template events.
	 *
	 * @var array
	 */
	private $all_template_events = array();

	/**
	 * Threshold severity.
	 *
	 * @var int
	 */
	private $threshold_severity = null;

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

		$threshold = get_option( 'woocommerce_block_template_logging_threshold', \WC_Log_Levels::WARNING );
		if ( ! \WC_Log_Levels::is_valid_level( $threshold ) ) {
			$threshold = \WC_Log_Levels::INFO;
		}

		$this->threshold_severity = \WC_Log_Levels::get_level_severity( $threshold );
	}

	/**
	 * Get all template events for a given template.
	 *
	 * @param string $template_id Template ID.
	 */
	public function get_template_events( string $template_id ): array {
		return isset( $this->all_template_events[ $template_id ] )
			? $this->all_template_events[ $template_id ]
			: array();
	}

	/**
	 * Log an event.
	 *
	 * @param string                 $event_type      Event type.
	 * @param BlockTemplateInterface $template        Template.
	 * @param ContainerInterface     $container       Container.
	 * @param BlockInterface         $block           Block.
	 * @param array                  $additional_info Additional info.
	 */
	public function log( string $event_type, BlockTemplateInterface $template, ContainerInterface $container, $block, $additional_info = array() ) {
		if ( ! isset( self::$event_types[ $event_type ] ) ) {
			/* translators: 1: WC_Logger::log 2: level */
			wc_doing_it_wrong( __METHOD__, sprintf( __( '%1$s was called with an invalid event type "%2$s".', 'woocommerce' ), '<code>BlockTemplateLogger::log</code>', $event_type ), '8.4' );
		}

		$event_type_info = isset( self::$event_types[ $event_type ] )
			? array_merge(
				self::$event_types[ $event_type ],
				array(
					'event_type' => $event_type,
				)
			)
			: array(
				'level'      => \WC_Log_Levels::ERROR,
				'event_type' => $event_type,
				'message'    => 'Unknown error.',
			);

		if ( ! $this->should_handle( $event_type_info['level'] ) ) {
			return;
		}

		$this->log_to_logger( $event_type_info, $template, $container, $block, $additional_info );
		$this->add_template_event( $event_type_info, $template, $container, $block, $additional_info );
	}

	/**
	 * Should the logger handle a given level?
	 *
	 * @param int $level Level to check.
	 */
	private function should_handle( $level ) {
		return $this->threshold_severity <= \WC_Log_Levels::get_level_severity( $level );
	}

	/**
	 * Log to the logger.
	 *
	 * @param array                  $event_type_info Event type info.
	 * @param BlockTemplateInterface $template        Template.
	 * @param ContainerInterface     $container       Container.
	 * @param BlockInterface         $block           Block.
	 * @param array                  $additional_info Additional info.
	 */
	private function log_to_logger( array $event_type_info, BlockTemplateInterface $template, ContainerInterface $container, BlockInterface $block, array $additional_info = array() ) {
		$info = array_merge(
			array(
				'template'  => $template,
				'container' => $container,
				'block'     => $block,
			),
			$additional_info
		);

		$message = $this->format_message( $event_type_info['message'], $info );

		$this->logger->log(
			$event_type_info['level'],
			$message,
			array( 'source' => 'block_template' )
		);
	}

	/**
	 * Add a template event.
	 *
	 * @param array                  $event_type_info Event type info.
	 * @param BlockTemplateInterface $template        Template.
	 * @param ContainerInterface     $container       Container.
	 * @param BlockInterface         $block           Block.
	 * @param array                  $additional_info Additional info.
	 */
	private function add_template_event( array $event_type_info, BlockTemplateInterface $template, ContainerInterface $container, BlockInterface $block, array $additional_info = array() ) {
		$template_id = $template->get_id();

		if ( ! isset( $this->all_template_events[ $template_id ] ) ) {
			$this->all_template_events[ $template_id ] = array();
		}

		$template_events = &$this->all_template_events[ $template_id ];

		$template_events[] = array(
			'level'           => $event_type_info['level'],
			'event_type'      => $event_type_info['event_type'],
			'message'         => $event_type_info['message'],
			'container'       => $container instanceof BlockContainerInterface
				? array(
					'id'   => $container->get_id(),
					'name' => $container->get_name(),
				)
				: null,
			'block'           => array(
				'id'   => $block->get_id(),
				'name' => $block->get_name(),
			),
			'additional_info' => $this->format_info( $additional_info ),
		);
	}

	/**
	 * Format a message for logging.
	 *
	 * @param string $message Message to log.
	 * @param array  $info    Additional info to log.
	 */
	private function format_message( string $message, array $info = array() ): string {
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
		return array(
			'message' => $exception->getMessage(),
			'source'  => "{$exception->getFile()}: {$exception->getLine()}",
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			'trace'   => print_r( $this->format_exception_trace( $exception->getTrace() ), true ),
		);
	}

	/**
	 * Format an exception trace for logging.
	 *
	 * @param array $trace Exception trace to format.
	 */
	private function format_exception_trace( array $trace ): array {
		$formatted_trace = array();

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
