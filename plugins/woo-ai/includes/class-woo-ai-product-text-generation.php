<?php
/**
 * Woo AI Product Text Generation Class
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 */
class Woo_AI_Product_Text_Generation {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_woo_ai_register_script' ) );
		add_action( 'media_buttons', array( $this, 'add_gpt_button' ), 40 );
		add_filter( 'the_editor', array( $this, 'add_gpt_form' ), 10, 1 );

		$ajax_event = 'generate_product_description';

		add_action( 'wp_ajax_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		// WC AJAX can be used for frontend ajax requests.
		add_action( 'wc_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
	}

	/**
	 * Enqueue the styles and JS
	 */
	public function add_woo_ai_register_script() {
		$script_path       = '/../build/index.js';
		$script_asset_path = dirname( __FILE__ ) . '/../build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => filemtime( $script_path ),
			);
		$script_url        = plugins_url( $script_path, __FILE__ );

		$script_asset['dependencies'][] = WC_ADMIN_APP; // Add WCA as a dependency to ensure it loads first.

		wp_register_script(
			'woo-ai',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
		wp_enqueue_script( 'woo-ai' );

		$css_file_version = filemtime( dirname( __FILE__ ) . '/../build/index.css' );

		wp_register_style(
			'wp-components',
			plugins_url( 'dist/components/style.css', __FILE__ ),
			array(),
			$css_file_version
		);

		wp_register_style(
			'woo-ai',
			plugins_url( '/../build/index.css', __FILE__ ),
			// Add any dependencies styles may have, such as wp-components.
			array(
				'wp-components',
			),
			$css_file_version
		);

		wp_enqueue_style( 'woo-ai' );
	}


	/**
	 * Add gpt button to the editor.
	 *
	 * @param String $editor_id Editor Id.
	 */
	public function add_gpt_button( $editor_id ) {
		if ( 'content' !== $editor_id || ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) ) {
			return;
		}

		echo '<button class="button wp-media-button wc-write-it-for-me" type="button">' . esc_html__( 'Write it for me (beta)', 'woocommerce' ) . '</button>';

	}

	/**
	 * Add gpt form to the editor.
	 *
	 * @param String $content Gpt form content.
	 */
	public function add_gpt_form( $content ) {
		global $post;

		// Check if the current post type is 'product'.
		if ( 'product' === $post->post_type ) {

			// Check if the content contains the specific editor ID.
			$editor_container_id       = 'wp-content-editor-container';
			$editor_container_position = strpos( $content, $editor_container_id );

			if ( false !== $editor_container_position ) {
				$gpt_form =
					'<div class="woocommerce-gpt-integration">' .
						'<a id="woocommerce-product-description-gpt-hide" href="#" title="' .
							esc_html__( 'Hide', 'woocommerce' ) .
						'"></a>' .
						'<label for="woocommerce-product-description-gpt-about">' .
							esc_html__( 'About your product', 'woocommerce' ) .
						'</label>' .
						'<div id="woocommerce-product-description-gpt-about-wrapper" class="textarea-wrapper field">' .
							'<textarea id="woocommerce-product-description-gpt-about" placeholder="' . esc_html__( 'e.g. organic and sustainable skin cleanser for women', 'woocommerce' ) . '"></textarea>' .
						'</div>' .
						'<label for="woocommerce-product-description-gpt-voice-tone">' .
							esc_html__( 'Tone of voice', 'woocommerce' ) .
						'</label>' .
						'<div id="woocommerce-product-description-gpt-voice-tone-wrapper" class="select-wrapper">' .
						'<select id="woocommerce-product-description-gpt-voice-tone" class="field" aria-describedby="woocommerce-product-description-gpt-voice-tone-description">' .
							'<option value="casual">' . esc_html__( 'Casual', 'woocommerce' ) . '</option>' .
							'<option value="formal">' . esc_html__( 'Formal', 'woocommerce' ) . '</option>' .
							'<option value="flowery">' . esc_html__( 'Flowery', 'woocommerce' ) . '</option>' .
							'<option value="convincing">' . esc_html__( 'Convincing', 'woocommerce' ) . '</option>' .
						'</select>' .
						'<p id="woocommerce-product-description-gpt-voice-tone-description">' .
							esc_html__( 'Relaxed, informal, conversational tone. Like chatting with a friend.', 'woocommerce' ) .
						'</p>' .
						'</div>' .
						'<div class="woocommerce-gpt-actions-wrapper">' .
						'<button id="woocommerce-product-description-gpt-action-accept" class="button button-primary gpt-action" action="write" type="button">' .
							esc_html__( 'Write description', 'woocommerce' ) .
						'</button>' .
						'<div class="woocommerce-gpt-extra-actions-wrapper hidden">' .
						'<button id="woocommerce-product-description-gpt-action-simplify" class="button button-tertiary gpt-action" action="simplify" type="button">' .
							esc_html__( 'Simplify it', 'woocommerce' ) .
						'</button>' .
						'<button id="woocommerce-product-description-gpt-action-longer" class="button button-tertiary gpt-action" action="more" type="button">' .
							esc_html__( 'Make it longer', 'woocommerce' ) .
						'</button>' .
						'<button id="woocommerce-product-description-gpt-action-rewrite" class="button button-primary gpt-action" action="rewrite" type="button">' .
							esc_html__( 'Rewrite', 'woocommerce' ) .
						'</button>' .
						'</div>' .
						'</div>' .
					'</div>';
				$content  = $gpt_form . $content;
			}
		}

		return $content;
	}

	/**
	 * Generate ChatGPT product description via AJAX.
	 *
	 * @since 3.4.0
	 */
	public static function generate_product_description() {
		$api_url = 'https://api.openai.com/v1/chat/completions';

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$product_description  = isset( $_POST['product_description'] ) ? wc_clean( wp_unslash( $_POST['product_description'] ) ) : '';
		$tone                 = isset( $_POST['tone'] ) ? wc_clean( wp_unslash( $_POST['tone'] ) ) : '';
		$existing_description = isset( $_POST['existing_description'] ) ? wc_clean( wp_unslash( $_POST['existing_description'] ) ) : '';
		$chatgpt_action       = isset( $_POST['chatgpt_action'] ) ? wc_clean( wp_unslash( $_POST['chatgpt_action'] ) ) : '';
		// phpcs:enable

		$prompt_by_action = array(
			'more'     => 'Make my last message longer, without losing any important information.',
			'simplify' => 'Simplify my last message, without losing any important information.',
		);

		$messages = array(
			array(
				'role'    => 'user',
				'content' => "Write a product description with $tone tone, from the following features: '$product_description'.",
			),
		);

		if ( $existing_description && array_key_exists( $chatgpt_action, $prompt_by_action ) ) {
			$messages[] = array(
				'role'    => 'assistant',
				'content' => $existing_description,
			);
			$messages[] = array(
				'role'    => 'user',
				'content' => $prompt_by_action[ $chatgpt_action ],
			);
		}

		// Configure curl request.
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $api_url );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . OPEN_AI_KEY,
			)
		);

		// Prepare the POST data.
		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		$post_data = array(
			'messages'    => $messages,
			'model'       => 'gpt-3.5-turbo',
			'temperature' => apply_filters( 'experimental_woocommerce_chatgpt_product_description_temperature', 1 ),
		);
		// phpcs:enable
		$post_data_json = json_encode( $post_data );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data_json );

		// Execute the request and get the response.
		$response = curl_exec( $ch );

		// Check for errors.
		if ( curl_error( $ch ) ) {
			echo 'Error: ' . esc_html( curl_error( $ch ) );
		} else {
			// Decode the JSON response.
			$response_data = json_decode( $response, true );

			// Extract and display the generated text.
			$generated_text = $response_data['choices'][0]['message']['content'];
			echo $generated_text;
		}

		// Close the curl session.
		curl_close( $ch );
		wp_die();
	}

}

new Woo_AI_Product_Text_Generation();
