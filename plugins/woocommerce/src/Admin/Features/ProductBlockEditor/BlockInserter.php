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
    public function get_content_with_block_insertions( $content, $post = null ) {
        $new_content = '';
        $p           = new \WP_HTML_Tag_Processor( $content );

        while( $p->next_token() ) {
            switch ( $p->get_token_type() ) {
                case '#comment':
                    $text       = $p->get_modifiable_text();
                    $block_name = $this->get_block_name_from_comment( $text );
                    $is_closer  =  str_starts_with( $text, ' /wp:' );

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

                    $new_content   .= '<!--' . $text . '-->';

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
                    $new_content .= $this->normalize_tag( $p );
                    break;
                case '#text':
                    $new_content .= $p->get_modifiable_text();
                    break;
            }
        }

        return $new_content;
    }

    /**
     * Normalize tag.
     *
     * @return string Normalized tag.
     */
    private function normalize_tag( $p ) {
        if ( null === $p->get_tag() ) {
            return null;
        }
    
        $tag_name = strtolower( $p->get_tag() );
    
        if ( $p->is_tag_closer() ) {
            return "</{$tag_name}>";
        }
    
        $attributes = $p->get_attribute_names_with_prefix( '' );

        if ( null === $attributes ) {
            return "<{$tag_name}>";
        }

        $builder = new \WP_HTML_Tag_Processor( "<{$tag_name}>" );
        $builder->next_tag();
        foreach ( $attributes as $name ) {
            $builder->set_attribute( $name, $p->get_attribute( $name ) );
        }

        return $builder->get_updated_html();
    }

    /**
     * Get the attributes string used in tags.
     *
     * @param \WP_HTML_Tag_Processor $p WP HTML Tag Processor.
     * @return string
     */
    public function get_attributes_string( $p ) {
        $string = '';

        if ( ! method_exists( $p, 'get_attributes' ) ) {
            return $string;
        }

        $html   = $p->get_updated_html();
        foreach ( $p->get_attributes() as $attribute ) {
            $string .= ' ' . substr( $html, $attribute->start, $attribute->length );
        }
        return $string;
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
