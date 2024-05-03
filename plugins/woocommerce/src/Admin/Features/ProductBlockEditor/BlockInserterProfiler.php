<?php
/**
 * WooCommerce Block Inserter
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * Class that assists in template extensibility and block insertion.
 */
class BlockInserterProfiler {

    private $test_content = <<<HTML
        <!-- wp:heading -->
        <h2 class="wp-block-heading">Test content</h2>
        <!-- /wp:heading -->

        <!-- wp:paragraph -->
        <p>Here's some test content for profiling extensibility in block hooks and block insertions.</p>
        <!-- /wp:paragraph -->

        <!-- wp:group {"layout":{"type":"constrained"}} -->
        <div class="wp-block-group"><!-- wp:image {"id":924,"width":"620px","height":"auto","sizeSlug":"full","linkDestination":"none"} -->
        <figure class="wp-block-image size-full is-resized"><img src="http://test.local/wp-content/uploads/2023/04/vneck-tee-2.jpg" alt="" class="wp-image-924" style="width:620px;height:auto"/></figure>
        <!-- /wp:image --></div>
        <!-- /wp:group -->

        <!-- wp:pullquote -->
        <figure class="wp-block-pullquote"><blockquote><p>If you're not first, you're last</p><cite>Ricky Bobby</cite></blockquote></figure>
        <!-- /wp:pullquote -->

        <!-- wp:list -->
        <ul><!-- wp:list-item -->
        <li>Tests parsing of content</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>Tests block insertion</li>
        <!-- /wp:list-item -->

        <!-- wp:list-item -->
        <li>Test serialization of blocks</li>
        <!-- /wp:list-item --></ul>
        <!-- /wp:list -->

        <!-- wp:paragraph -->
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
        <!-- /wp:paragraph -->
    HTML;

    private $n = 1000;

    /**
     * Init.
     */
    public function init() {
        $this->run_block_hooks_profiler();
        $this->run_block_insertion_profiler();
    }

    /**
     * Extend the post content.
     */
    private function run_block_insertion_profiler() {
        $block_inserter = new BlockInserter();
        $start = microtime( true );

        for ( $i = 0; $i < $this->n; $i++ ) {
            $block_inserter->get_content_with_block_insertions( $this->test_content );
        }

        $elapsed_time = microtime( true ) - $start;

        error_log( 'Block insertion' );
        error_log( 'Time: ' . $elapsed_time );
        error_log( 'Count: ' . $this->n );
    }

    /**
     * Extend the post content.
     */
    private function run_block_hooks_profiler() {
        $template                 = new \WP_Block_Template();
        $template->id             = 'woocommerce//block-insertion-hooks-profiler';
        $template->theme          = 'n/a';
        $template->content        = $this->test_content;
        $template->slug           = 'block-insertion-hooks-profiler';
        $template->source         = 'plugin';
        $template->area           = 'n/a';
        $template->type           = 'n/a';
        $template->title          = 'Block insertion hooks profiler';
        $template->description    = 'A test template to determine performance of hooks vs insertion';
        $template->status         = 'publish';
        $template->has_theme_file = false;
        $template->is_custom      = false;
        $template->modified       = null;

        $start = microtime( true );

        for ( $i = 0; $i < $this->n; $i++ ) {
            $before_block_visitor = '_inject_theme_attribute_in_template_part_block';
            $after_block_visitor  = null;
            $hooked_blocks        = get_hooked_blocks();
            if ( ! empty( $hooked_blocks ) || has_filter( 'hooked_block_types' ) ) {
                $before_block_visitor = make_before_block_visitor( $hooked_blocks, $template );
                $after_block_visitor  = make_after_block_visitor( $hooked_blocks, $template );
            }
            $blocks = parse_blocks( $template->content );
            traverse_and_serialize_blocks( $blocks, $before_block_visitor, $after_block_visitor );
        }

        $elapsed_time = microtime( true ) - $start;

        error_log( 'Block hooks' );
        error_log( 'Time: ' . $elapsed_time );
        error_log( 'Count: ' . $this->n );
    }

}
