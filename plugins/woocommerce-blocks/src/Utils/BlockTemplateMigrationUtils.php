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
	 * @param string $template_slug Template slug.
	 */
	public static function migrate_page( $template_slug ) {
		// Get the block template for this page. If it exists, we won't migrate because the user already has custom content.
		$block_template = BlockTemplateUtils::get_block_template( 'woocommerce/woocommerce//page-' . $template_slug, 'wp_template' );
		// If we were unable to get the block template, bail. Try again later.
		if ( ! $block_template ) {
			return;
		}

		// Skip migration if the theme has a template file for this page.
		$theme_template = BlockTemplateUtils::get_block_template( get_stylesheet() . '//page-' . $template_slug, 'wp_template' );
		if ( $theme_template ) {
			return self::set_has_migrated_page( $template_slug, 'theme-file-exists' );
		}

		// If a custom template is present already, no need to migrate.
		if ( $block_template->wp_id ) {
			return self::set_has_migrated_page( $template_slug, 'custom-template-exists' );
		}

		$template_content = self::get_default_template( $template_slug );

		if ( self::create_custom_template( $block_template, $template_content ) ) {
			return self::set_has_migrated_page( $template_slug );
		}
	}

	/**
	 * Prepare default page template.
	 *
	 * @param string $template_slug Template slug.
	 * @return string
	 */
	protected static function get_default_template( $template_slug ) {

		$default_template_content = '
			<!-- wp:group {"layout":{"inherit":true,"type":"constrained"}} -->
				<div class="wp-block-group"><!-- wp:woocommerce/page-content-wrapper {"page":"' . $template_slug . '"} -->
				<!-- wp:post-title {"align":"wide", "level":1} /-->
				<!-- wp:post-content {"align":"wide"} /-->
				<!-- /wp:woocommerce/page-content-wrapper --></div>
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
