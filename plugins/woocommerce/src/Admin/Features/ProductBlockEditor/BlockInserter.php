<?php
/**
 * WooCommerce Block Inserter
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

/**
 * Class that assists in template extensibility and block insertion.
 */
class BlockInserter {

    /**
     * Init.
     */
    public function init() {
        add_filter( 'posts_results', array( $this, 'extend_post_content' ) );
    }

    /**
     * Extend the post content.
     *
     * @param array $posts Array of WP_Post
     * @return array
     */
    public function extend_post_content( $posts ) {
        foreach ( $posts as $index => $post ) {
            if ( ! $post->post_content ) {
                continue;
            }

            $post->post_content = $this->get_content_with_block_insertions( $post->post_content, $post );
            $posts[ $index ]    = $post;
        }

        return $posts;
    }

    /**
     * Get the post content with added block insertions.
     *
     * @param string  $content Content.
     * @param WP_Post $post Post.
     * @return string
     */
    private function get_content_with_block_insertions( $content, $post ) {
        $new_content = '';
        $p           = new \WP_HTML_Tag_Processor( $content );

        while( $p->next_token() ) {
            switch ( $p->get_token_type() ) {
                case '#comment':
                    $block_name = $this->get_block_name_from_comment( $p->get_modifiable_text() );
                    $is_closer  = strpos( $p->get_modifiable_text(), ' /wp:' ) === 0;

                    if ( $block_name ) {
                        $new_content   .= $this->get_content_with_block_insertions(
                            apply_filters(
                                'block_insertions',
                                '',
                                $block_name,
                                $is_closer ? 'last_child' : 'before',
                                $post
                            ),
                            $post
                        );
                    }

                    $new_content   .= '<!--' . $p->get_modifiable_text() . '-->';

                    if ( $block_name ) {
                        $new_content   .= $this->get_content_with_block_insertions(
                            apply_filters(
                                'block_insertions',
                                '',
                                $block_name,
                                $is_closer ? 'after' : 'first_child',
                                $post
                            ),
                            $post
                        );
                    }

                    break;
                case '#tag':
                    $new_content .= ! $p->is_tag_closer()
                        ? '<' . strtolower( $p->get_tag() ) . '>'
                        : '</' . strtolower( $p->get_tag() ) . '>';
                    break;
                case '#text':
                    $new_content .= $p->get_modifiable_text();
                    break;
            }
        }

        return $new_content;
    }

    /**
     * Get a block name from a comment.
     *
     * @param string $comment_text Comment text.
     * @return string|bool
     */
    private function get_block_name_from_comment( $comment_text ) {
        preg_match( '/wp:([a-z_\-\/]*)/i', $comment_text, $matches );

        if ( isset( $matches[1] ) ) {
            return $matches[1];
        }

        return false;
    }

    /**
     * Check if a comment is a block tag closer.
     *
     * @param string $comment_text Comment text.
     * @return bool
     */
    private function is_block_tag_closer( $comment_text ) {
        return strpos( $comment_text, ' /wp:' ) === 0;
    }
}
