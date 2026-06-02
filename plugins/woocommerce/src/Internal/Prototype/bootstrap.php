<?php
/**
 * Prototype bootstrap - not for merge.
 *
 * Initialises all prototype dev tools and improvements.
 * Remove this file (and the require_once in woocommerce.php) to clean up.
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Prototype\DevPanel;
use Automattic\WooCommerce\Internal\Prototype\Improvements\CustomFieldsPolish;
use Automattic\WooCommerce\Internal\Prototype\Improvements\DownloadableFilesPolish;
use Automattic\WooCommerce\Internal\Prototype\Improvements\EditorControls;
use Automattic\WooCommerce\Internal\Prototype\Improvements\IconModernisation;
use Automattic\WooCommerce\Internal\Prototype\Improvements\MaxWidth;
use Automattic\WooCommerce\Internal\Prototype\Improvements\PublishMetabox;
use Automattic\WooCommerce\Internal\Prototype\Improvements\ReorderControls;
use Automattic\WooCommerce\Internal\Prototype\Improvements\SavePublishClarity;
use Automattic\WooCommerce\Internal\Prototype\Improvements\SpacingRefinement;
use Automattic\WooCommerce\Internal\Prototype\Improvements\TaxonomyPanelPolish;
use Automattic\WooCommerce\Internal\Prototype\Improvements\TypographyHierarchy;

DevPanel::init();
ReorderControls::init();
IconModernisation::init();
MaxWidth::init();
SavePublishClarity::init();
SpacingRefinement::init();
TaxonomyPanelPolish::init();
DownloadableFilesPolish::init();
CustomFieldsPolish::init();
TypographyHierarchy::init();
EditorControls::init();
PublishMetabox::init();
