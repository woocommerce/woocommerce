<?php

namespace Automattic\WooCommerce\Tests\StoreApi;

use Automattic\WooCommerce\Tests\Framework\WC_Unit_Test_Case;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\MockSessionHandler;

/**
 * Test class for MockSessionHandler.
 */
class MockSessionHandlerTest extends WC_Unit_Test_Case {

    /**
     * Test setting the session cookie with the default delimiter.
     */
    public function test_set_customer_session_cookie_default_delimiter() {
        $session_handler = new MockSessionHandler();
        $session_handler->set_customer_session_cookie(true);

        $cookie_name = $session_handler->get_cookie_name();
        $this->assertNotEmpty($_COOKIE[$cookie_name]);
        $this->assertStringContainsString('||', $_COOKIE[$cookie_name]);
    }

    /**
     * Test setting the session cookie with a custom delimiter.
     */
    public function test_set_customer_session_cookie_custom_delimiter() {
        $session_handler = new MockSessionHandler();
        add_filter('woocommerce_session_cookie_delimiter', function() {
            return '|';
        });

        $session_handler->set_customer_session_cookie(true);

        $cookie_name = $session_handler->get_cookie_name();
        $this->assertNotEmpty($_COOKIE[$cookie_name]);
        $this->assertStringContainsString('|', $_COOKIE[$cookie_name]);
    }


}

