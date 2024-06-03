<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\Settings;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register;

class ConfigureSettingsProducts extends MapFieldsToOptions implements StepProcessor {
	protected array $options_map = [
		// General
		'shop_page' => 'woocommerce_shop_page_id',
		'enable_ajax_add_to_cart_button' => 'woocommerce_enable_ajax_add_to_cart',
		'redirect_to_the_cart_page_after_successfrul_addition' => 'woocommerce_cart_redirect_after_add',
		"placeholder_image" => 'woocommerce_placeholder_image',
		"weight_unit"=> "woocommerce_weight_unit",
		"dimensions_unit"=> "woocommerce_dimensions_unit",
		"enable_product_reviews"=> "woocommerce_enable_reviews",
		"show_verified_owner_label_on_customer_reviews"=> "woocommerce_review_rating_verification_label",
		"reviews_can_only_be_left_by_verified_owners"=> "woocommerce_review_rating_verification_required",
		"enable_start_rating_on_reviews"=> "woocommerce_enable_review_rating",
		"star_ratings_should_be_required_not_optional"=> "woocommerce_review_rating_required",

		// Invesntory
		"manage_stock"=> "woocommerce_manage_stock",
		"hold_stock_mins"=> "woocommerce_hold_stock_minutes",
		"enable_low_stock_notifications"=> "woocommerce_notify_low_stock",
		"enable_out_of_stock_notifications"=> "woocommerce_notify_no_stock",
		"notification_recipients"=> "woocommerce_stock_email_recipient",
		"low_stock_threshold"=> "woocommerce_notify_low_stock_amount",
		"out_of_stock_threshold"=> "woocommerce_notify_no_stock_amount",
		"hide_out_of_stock_items_from_the_catalog"=> "woocommerce_hide_out_of_stock_items",
		"stock_display_format"=> "woocommerce_stock_format",

		// Downloadable products
		"file_download_method"=> "woocommerce_file_download_method",
		"allow_using_redirect_mode_as_last_resort"=> "woocommerce_downloads_redirect_fallback_allowed",
		"download_require_login"=> "woocommerce_downloads_require_login",
		"grant_access_to_downlodable_products_after_payment"=> "woocommerce_downloads_grant_access_after_payment",
		"open_downloadable_files_in_the_browser"=> "woocommerce_downloads_deliver_inline",
		"append_unique_string_to_filename_for_security"=> "woocommerce_downloads_add_hash_to_filename",

		// Advanced
		"use_the_product_attributes_lookup_table_for_catalog_filtering"=> "woocommerce_attribute_lookup_enabled",
		"update_the_table_directly_upon_product_changes"=> "woocommerce_attribute_lookup_direct_updates",
		"set_product_featured_image_when_uploaded_image_file_name_matches_product_sku"=> "woocommerce_product_match_featured_image_by_sku"
	];

	public function process( $schema ): StepProcessorResult {
		$process_result = parent::process( $schema );

		// Take care of 'approved_downlaod_directories' field.
		if ( isset( $schema->approved_downlaod_directories ) ) {
			$register = new Register();
			foreach ($schema->approved_downlaod_directories as $dir) {
				try {
					$register->add_approved_directory( $dir->directory_url, $dirs->enable ?? false );
				} catch (\Exception $e) {
					$process_result->add_error($e->getMessage());
				}
			}
		}

		return $process_result;
	}
}
