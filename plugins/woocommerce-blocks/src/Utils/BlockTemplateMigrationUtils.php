<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Utility methods used for migrating pages to block templates.
 * {@internal This class and its methods should only be used within the BlockTemplateController.php and is not intended for public use.}
 */
class BlockTemplateMigrationUtils {

	/**
	 * Check if a page has been migrated to a template.
	 *
	 * @param string $page_id Page ID.
	 * @return boolean
	 */
	public static function has_migrated_page( $page_id ) {
		return (bool) get_option( 'has_migrated_' . $page_id, false );
	}

	/**
	 * Stores an option to indicate that a template has been migrated.
	 *
	 * @param string $page_id Page ID.
	 * @param string $status Status of the migration.
	 */
	public static function set_has_migrated_page( $page_id, $status = 'success' ) {
		update_option( 'has_migrated_' . $page_id, $status );
	}

	/**
	 * Migrates a page to a template if needed.
	 *
	 * @param string   $template_slug Template slug.
	 * @param \WP_Post $page Page object.
	 */
	public static function migrate_page( $template_slug, $page ) {
		// Get the block template for this page. If it exists, we won't migrate because the user already has custom content.
		$block_template = BlockTemplateUtils::get_block_template( 'woocommerce/woocommerce//' . $template_slug, 'wp_template' );

		// If we were unable to get the block template, bail. Try again later.
		if ( ! $block_template ) {
			return;
		}

		// If a custom template is present already, no need to migrate.
		if ( $block_template->wp_id ) {
			return self::set_has_migrated_page( $template_slug, 'custom-template-exists' );
		}

		// Use the page template if it exists, which we'll use over our default template if found.
		$page_template    = self::get_page_template( $page );
		$default_template = self::get_default_template( $page );
		$template_content = $page_template ?: $default_template;

		// If at this point we have no content to migrate, bail.
		if ( ! $template_content ) {
			return self::set_has_migrated_page( $template_slug, 'no-content' );
		}

		if ( self::create_custom_template( $block_template, $template_content ) ) {
			return self::set_has_migrated_page( $template_slug );
		}
	}

	/**
	 * Get template for a page following the page hierarchy.
	 *
	 * @param \WP_Post|null $page Page object.
	 * @return string
	 */
	protected static function get_page_template( $page ) {
		$templates = array();

		if ( $page && $page->ID ) {
			$template = get_page_template_slug( $page->ID );

			if ( $template && 0 === validate_file( $template ) ) {
				$templates[] = $template;
			}

			$pagename = $page->post_name;

			if ( $pagename ) {
				$pagename_decoded = urldecode( $pagename );
				if ( $pagename_decoded !== $pagename ) {
					$templates[] = "page-{$pagename_decoded}";
				}
				$templates[] = "page-{$pagename}";
			}
		}

		$block_template = false;

		foreach ( $templates as $template ) {
			$block_template = BlockTemplateUtils::get_block_template( get_stylesheet() . '//' . $template, 'wp_template' );

			if ( $block_template && ! empty( $block_template->content ) ) {
				break;
			}
		}

		return $block_template ? $block_template->content : '';
	}

	/**
	 * Prepare default page template.
	 *
	 * @param \WP_Post $page Page object.
	 * @return string
	 */
	protected static function get_default_template( $page ) {
		if ( ! $page || empty( $page->post_content ) ) {
			return '';
		}
		$default_template_content = '
			<!-- wp:group {"layout":{"inherit":true}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":1} -->
				<h1 class="wp-block-heading">' . wp_kses_post( $page->post_title ) . '</h1>
				<!-- /wp:heading -->
				' . wp_kses_post( $page->post_content ) . '
			</div>
			<!-- /wp:group -->
		';
		return self::get_block_template_part( 'header' ) . $default_template_content . self::get_block_template_part( 'footer' );
	}

	/**
	 * Create a custom template with given content.
	 *
	 * @param \WP_Block_Template|null $template Template object.
	 * @param string                  $content Template content.
	 * @return boolean Success.
	 */
	protected static function create_custom_template( $template, $content ) {

		$term = get_term_by( 'slug', $template->theme, 'wp_theme', ARRAY_A );

		if ( ! $term ) {
			$term = wp_insert_term( $template->theme, 'wp_theme' );
		}

		$template_id = wp_insert_post(
			[
				'post_name'    => $template->slug,
				'post_type'    => 'wp_template',
				'post_status'  => 'publish',
				'tax_input'    => array(
					'wp_theme' => $template->theme,
				),
				'meta_input'   => array(
					'origin' => $template->source,
				),
				'post_content' => $content,
			],
			true
		);

		wp_set_post_terms( $template_id, array( $term['term_id'] ), 'wp_theme' );

		return $template_id && ! is_wp_error( $template_id );
	}

	/**
	 * Returns the requested template part.
	 *
	 * @param string $part The part to return.
	 * @return string
	 */
	protected static function get_block_template_part( $part ) {
		$template_part = BlockTemplateUtils::get_block_template( get_stylesheet() . '//' . $part, 'wp_template_part' );
		if ( ! $template_part || empty( $template_part->content ) ) {
			return '';
		}
		return $template_part->content;
	}
}
