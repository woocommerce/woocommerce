<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\Pelago\Emogrifier\HtmlProcessor;

/**
 * Normalizes HTML:
 * - add a document type (HTML5) if missing
 * - disentangle incorrectly nested tags
 * - add HEAD and BODY elements (if they are missing)
 * - reformat the HTML
 */
final class HtmlNormalizer extends AbstractHtmlProcessor {}
