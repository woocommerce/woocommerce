<?php declare( strict_types = 1 );
namespace Automattic\WooCommerce\Blocks\BlockTypes\Reviews;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;

/**
 * ProductReviewForm class.
 */
class ProductReviewForm extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-review-form';

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @see $this->register_block_type()
	 * @param string $key Data to get, or default to everything.
	 * @return array|string|null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 * @return string Rendered block content.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! isset( $block->context['postId'] ) ) {
			return '';
		}

		if ( post_password_required( $block->context['postId'] ) ) {
			return;
		}

		$product = wc_get_product( $block->context['postId'] );

		if ( ! $product ) {
			return '';
		}

		if ( get_option( 'woocommerce_review_rating_verification_required' ) !== 'no' && ! wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) {
			return '<p class="woocommerce-verification-required">' . esc_html__( 'Only logged in customers who have purchased this product may leave a review.', 'woocommerce' ) . '</p>';
		}

		$classes = array( 'comment-respond' );
		if ( isset( $attributes['textAlign'] ) ) {
			$classes[] = 'has-text-align-' . $attributes['textAlign'];
		}
		if ( isset( $attributes['style']['elements']['link']['color']['text'] ) ) {
			$classes[] = 'has-link-color';
		}
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) );

		$commenter    = wp_get_current_commenter();
		$comment_form = array(
			/* translators: %s is product title */
			'title_reply'         => have_comments() ? esc_html__( 'Add a review', 'woocommerce' ) : sprintf( esc_html__( 'Be the first to review &ldquo;%s&rdquo;', 'woocommerce' ), get_the_title() ),
			/* translators: %s is product title */
			'title_reply_to'      => esc_html__( 'Leave a Reply to %s', 'woocommerce' ),
			'title_reply_before'  => '<span id="reply-title" class="comment-reply-title" role="heading" aria-level="3">',
			'title_reply_after'   => '</span>',
			'comment_notes_after' => '',
			'label_submit'        => esc_html__( 'Submit', 'woocommerce' ),
			'logged_in_as'        => '',
			'comment_field'       => '',
		);

		$name_email_required = (bool) get_option( 'require_name_email', 1 );
				$fields      = array(
					'author' => array(
						'label'        => __( 'Name', 'woocommerce' ),
						'type'         => 'text',
						'value'        => $commenter['comment_author'],
						'required'     => $name_email_required,
						'autocomplete' => 'name',
					),
					'email'  => array(
						'label'        => __( 'Email', 'woocommerce' ),
						'type'         => 'email',
						'value'        => $commenter['comment_author_email'],
						'required'     => $name_email_required,
						'autocomplete' => 'email',
					),
				);

				$comment_form['fields'] = array();

				foreach ( $fields as $key => $field ) {
					$field_html  = '<p class="comment-form-' . esc_attr( $key ) . '">';
					$field_html .= '<label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] );

					if ( $field['required'] ) {
						$field_html .= '&nbsp;<span class="required">*</span>';
					}

					$field_html .= '</label><input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="' . esc_attr( $field['type'] ) . '" autocomplete="' . esc_attr( $field['autocomplete'] ) . '" value="' . esc_attr( $field['value'] ) . '" size="30" ' . ( $field['required'] ? 'required' : '' ) . ' /></p>';

					$comment_form['fields'][ $key ] = $field_html;
				}

				$account_page_url = wc_get_page_permalink( 'myaccount' );
				if ( $account_page_url ) {
					/* translators: %s opening and closing link tags respectively */
					$comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf( esc_html__( 'You must be %1$slogged in%2$s to post a review.', 'woocommerce' ), '<a href="' . esc_url( $account_page_url ) . '">', '</a>' ) . '</p>';
				}

				if ( wc_review_ratings_enabled() ) {
					$comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating" id="comment-form-rating-label">' .
						esc_html__( 'Your rating', 'woocommerce' ) .
						( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) .
					'</label><select name="rating" id="rating" required>
					<option value="">' . esc_html__( 'Rate&hellip;', 'woocommerce' ) . '</option>
					<option value="5">' . esc_html__( 'Perfect', 'woocommerce' ) . '</option>
					<option value="4">' . esc_html__( 'Good', 'woocommerce' ) . '</option>
					<option value="3">' . esc_html__( 'Average', 'woocommerce' ) . '</option>
					<option value="2">' . esc_html__( 'Not that bad', 'woocommerce' ) . '</option>
					<option value="1">' . esc_html__( 'Very poor', 'woocommerce' ) . '</option>
				</select></div>';
				}

				$comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your review', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';

				add_filter( 'comment_form_defaults', 'post_comments_form_block_form_defaults' );

				ob_start();
				echo '<div id="review_form_wrapper"><div id="review_form">';
				/**
				 * Filters the comment form arguments.
				 *
				 * @since 9.9.0
				 * @param array $comment_form The comment form arguments.
				 * @param int   $post_id      The post ID.
				 */
				comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ), $block->context['postId'] );
				echo '</div></div>';
				$form = ob_get_clean();

				remove_filter( 'comment_form_defaults', 'post_comments_form_block_form_defaults' );

				$form = str_replace( 'class="comment-respond"', $wrapper_attributes, $form );

				wp_enqueue_script( 'comment-reply' );

				return $form;
	}
}
