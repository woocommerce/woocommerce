<?php
/*
 * Copyright (c) 2013, 2014 MasterCard International Incorporated
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of
 * conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 * Neither the name of the MasterCard International Incorporated nor the names of its
 * contributors may be used to endorse or promote products derived from this software
 * without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

require_once(dirname(__FILE__) . '/Simplify/Constants.php');

class Simplify
{
    /**
     * @var string $publicKey public API key used to authenticate requests.
     */
    public static $publicKey;

    /**
     * @var string $privateKey private API key used to authenticate requests.
     */
    public static $privateKey;


    /**
     * @var string $apiBaseLiveUrl URL of the live API endpoint
     */
    public static $apiBaseLiveUrl = Simplify_Constants::API_BASE_LIVE_URL;

    /**
     * @var string $apiBaseSandboxUrl URL of the sandbox API endpoint
     */
    public static $apiBaseSandboxUrl = Simplify_Constants::API_BASE_SANDBOX_URL;

    /**
     * @var string $userAgent User-agent string send with requests.
     */
    public static $userAgent = null;

}

require_once(dirname(__FILE__) . '/Simplify/Object.php');
require_once(dirname(__FILE__) . '/Simplify/AccessToken.php');
require_once(dirname(__FILE__) . '/Simplify/Authentication.php');
require_once(dirname(__FILE__) . '/Simplify/PaymentsApi.php');
require_once(dirname(__FILE__) . '/Simplify/Exceptions.php');
require_once(dirname(__FILE__) . '/Simplify/Http.php');
require_once(dirname(__FILE__) . '/Simplify/ResourceList.php');
require_once(dirname(__FILE__) . '/Simplify/CardToken.php');
require_once(dirname(__FILE__) . '/Simplify/Chargeback.php');
require_once(dirname(__FILE__) . '/Simplify/Coupon.php');
require_once(dirname(__FILE__) . '/Simplify/Customer.php');
require_once(dirname(__FILE__) . '/Simplify/Deposit.php');
require_once(dirname(__FILE__) . '/Simplify/Event.php');
require_once(dirname(__FILE__) . '/Simplify/Invoice.php');
require_once(dirname(__FILE__) . '/Simplify/InvoiceItem.php');
require_once(dirname(__FILE__) . '/Simplify/Payment.php');
require_once(dirname(__FILE__) . '/Simplify/Plan.php');
require_once(dirname(__FILE__) . '/Simplify/Refund.php');
require_once(dirname(__FILE__) . '/Simplify/Subscription.php');
require_once(dirname(__FILE__) . '/Simplify/Webhook.php');
