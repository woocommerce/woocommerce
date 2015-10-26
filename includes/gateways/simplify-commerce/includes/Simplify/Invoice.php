<?php
/*
 * Copyright (c) 2013 - 2015 MasterCard International Incorporated
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


class Simplify_Invoice extends Simplify_Object {
    /**
     * Creates an Simplify_Invoice object
     * @param     array $hash a map of parameters; valid keys are:<dl style="padding-left:10px;">
     *     <dt><tt>billingAddress.city</tt></dt>    <dd>Billing address city of the location where the goods or services were supplied. [max length: 255, min length: 2] </dd>
     *     <dt><tt>billingAddress.country</tt></dt>    <dd>Billing address country of the location where the goods or services were supplied. [max length: 2, min length: 2] </dd>
     *     <dt><tt>billingAddress.line1</tt></dt>    <dd>Billing address line 1 of the location where the goods or services were supplied. [max length: 255] </dd>
     *     <dt><tt>billingAddress.line2</tt></dt>    <dd>Billing address line 2 of the location where the goods or services were supplied. [max length: 255] </dd>
     *     <dt><tt>billingAddress.name</tt></dt>    <dd>Billing address name of the location where the goods or services were supplied. Will use the customer name if not provided. [max length: 255] </dd>
     *     <dt><tt>billingAddress.state</tt></dt>    <dd>Billing address state of the location where the goods or services were supplied. [max length: 255] </dd>
     *     <dt><tt>billingAddress.zip</tt></dt>    <dd>Billing address zip of the location where the goods or services were supplied. [max length: 32] </dd>
     *     <dt><tt>businessAddress.city</tt></dt>    <dd>Address city of the business that is sending the invoice. [max length: 255, min length: 2] </dd>
     *     <dt><tt>businessAddress.country</tt></dt>    <dd>Address country of the business that is sending the invoice. [max length: 2, min length: 2] </dd>
     *     <dt><tt>businessAddress.line1</tt></dt>    <dd>Address line 1 of the business that is sending the invoice. [max length: 255] </dd>
     *     <dt><tt>businessAddress.line2</tt></dt>    <dd>Address line 2 of the business that is sending the invoice. [max length: 255] </dd>
     *     <dt><tt>businessAddress.name</tt></dt>    <dd>The name of the business that is sending the invoice. [max length: 255] </dd>
     *     <dt><tt>businessAddress.state</tt></dt>    <dd>Address state of the business that is sending the invoice. [max length: 255] </dd>
     *     <dt><tt>businessAddress.zip</tt></dt>    <dd>Address zip of the business that is sending the invoice. [max length: 32] </dd>
     *     <dt><tt>currency</tt></dt>    <dd>Currency code (ISO-4217). Must match the currency associated with your account. [max length: 3, min length: 3, default: USD] </dd>
     *     <dt><tt>customer</tt></dt>    <dd>The customer ID of the customer we are invoicing.  This is optional if invoiceToCopy or a name and email are provided </dd>
     *     <dt><tt>customerTaxNo</tt></dt>    <dd>The tax number or VAT id of the person to whom the goods or services were supplied. [max length: 255] </dd>
     *     <dt><tt>discountRate</tt></dt>    <dd>The discount percent as a decimal e.g. 12.5.  This is used to calculate the discount amount which is subtracted from the total amount due before any tax is applied. [max length: 6] </dd>
     *     <dt><tt>dueDate</tt></dt>    <dd>The date invoice payment is due.  If a late fee is provided this will be added to the invoice total is the due date has past. </dd>
     *     <dt><tt>email</tt></dt>    <dd>The email of the customer we are invoicing.  This is optional if customer or invoiceToCopy is provided.  A new customer will be created using the the name and email. </dd>
     *     <dt><tt>invoiceId</tt></dt>    <dd>User defined invoice id. If not provided the system will generate a numeric id. [max length: 255] </dd>
     *     <dt><tt>invoiceToCopy</tt></dt>    <dd>The id of an existing invoice to be copied.  This is optional if customer or a name and email are provided </dd>
     *     <dt><tt>items.amount</tt></dt>    <dd>Amount of the invoice item (the smallest unit of your currency). Example: 100 = $1.00USD [min value: -9999900, max value: 9999900] <strong>required </strong></dd>
     *     <dt><tt>items.description</tt></dt>    <dd>The description of the invoice item. [max length: 1024] </dd>
     *     <dt><tt>items.invoice</tt></dt>    <dd>The ID of the invoice this item belongs to. </dd>
     *     <dt><tt>items.product</tt></dt>    <dd>The product this invoice item refers to. </dd>
     *     <dt><tt>items.quantity</tt></dt>    <dd>Quantity of the item.  This total amount of the invoice item is the amount * quantity. [min value: 1, max value: 999999, default: 1] </dd>
     *     <dt><tt>items.reference</tt></dt>    <dd>User defined reference field. [max length: 255] </dd>
     *     <dt><tt>items.tax</tt></dt>    <dd>The tax ID of the tax charge in the invoice item. </dd>
     *     <dt><tt>lateFee</tt></dt>    <dd>The late fee amount that will be added to the invoice total is the due date is past due.  Value provided must be in the smallest unit of your currency. Example: 100 = $1.00USD [max value: 9999900] </dd>
     *     <dt><tt>memo</tt></dt>    <dd>A memo that is displayed to the customer on the invoice payment screen. [max length: 4000] </dd>
     *     <dt><tt>name</tt></dt>    <dd>The name of the customer we are invoicing.  This is optional if customer or invoiceToCopy is provided.  A new customer will be created using the the name and email. [max length: 50, min length: 2] </dd>
     *     <dt><tt>note</tt></dt>    <dd>This field can be used to store a note that is not displayed to the customer. [max length: 4000] </dd>
     *     <dt><tt>reference</tt></dt>    <dd>User defined reference field. [max length: 255] </dd>
     *     <dt><tt>shippingAddress.city</tt></dt>    <dd>Address city of the location where the goods or services were supplied. [max length: 255, min length: 2] </dd>
     *     <dt><tt>shippingAddress.country</tt></dt>    <dd>Address country of the location where the goods or services were supplied. [max length: 2, min length: 2] </dd>
     *     <dt><tt>shippingAddress.line1</tt></dt>    <dd>Address line 1 of the location where the goods or services were supplied. [max length: 255] </dd>
     *     <dt><tt>shippingAddress.line2</tt></dt>    <dd>Address line 2 of the location where the goods or services were supplied. [max length: 255] </dd>
     *     <dt><tt>shippingAddress.name</tt></dt>    <dd>Address name of the location where the goods or services were supplied. [max length: 255] </dd>
     *     <dt><tt>shippingAddress.state</tt></dt>    <dd>Address state of the location where the goods or services were supplied. [max length: 255] </dd>
     *     <dt><tt>shippingAddress.zip</tt></dt>    <dd>Address zip of the location where the goods or services were supplied. [max length: 32] </dd>
     *     <dt><tt>suppliedDate</tt></dt>    <dd>The date on which the goods or services were supplied. </dd>
     *     <dt><tt>taxNo</tt></dt>    <dd>The tax number or VAT id of the person who supplied the goods or services. [max length: 255] </dd>
     *     <dt><tt>type</tt></dt>    <dd>The type of invoice.  One of WEB or MOBILE. [valid values: WEB, MOBILE, default: WEB] </dd></dl>
     * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.<i/>
     * @return    Invoice a Invoice object.
     */
    static public function createInvoice($hash, $authentication = null) {

        $args = func_get_args();
        $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

        $instance = new Simplify_Invoice();
        $instance->setAll($hash);

        $object = Simplify_PaymentsApi::createObject($instance, $authentication);
        return $object;
    }




       /**
        * Deletes an Simplify_Invoice object.
        *
        * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
        */
        public function deleteInvoice($authentication = null) {

            $args = func_get_args();
            $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 1);

            $obj = Simplify_PaymentsApi::deleteObject($this, $authentication);
            $this->properties = null;
            return true;
        }


       /**
        * Retrieve Simplify_Invoice objects.
        * @param     array criteria a map of parameters; valid keys are:<dl style="padding-left:10px;">
        *     <dt><tt>filter</tt></dt>    <dd>Filters to apply to the list.  </dd>
        *     <dt><tt>max</tt></dt>    <dd>Allows up to a max of 50 list items to return. [min value: 0, max value: 50, default: 20]  </dd>
        *     <dt><tt>offset</tt></dt>    <dd>Used in paging of the list.  This is the start offset of the page. [min value: 0, default: 0]  </dd>
        *     <dt><tt>sorting</tt></dt>    <dd>Allows for ascending or descending sorting of the list.  The value maps properties to the sort direction (either <tt>asc</tt> for ascending or <tt>desc</tt> for descending).  Sortable properties are: <tt> id</tt><tt> invoiceDate</tt><tt> dueDate</tt><tt> datePaid</tt><tt> customer</tt><tt> status</tt><tt> dateCreated</tt>.</dd></dl>
        * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
        * @return    ResourceList a ResourceList object that holds the list of Invoice objects and the total
        *            number of Invoice objects available for the given criteria.
        * @see       ResourceList
        */
        static public function listInvoice($criteria = null, $authentication = null) {

            $args = func_get_args();
            $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

            $val = new Simplify_Invoice();
            $list = Simplify_PaymentsApi::listObject($val, $criteria, $authentication);

            return $list;
        }


        /**
         * Retrieve a Simplify_Invoice object from the API
         *
         * @param     string id  the id of the Invoice object to retrieve
         * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
         * @return    Invoice a Invoice object
         */
        static public function findInvoice($id, $authentication = null) {

            $args = func_get_args();
            $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

            $val = new Simplify_Invoice();
            $val->id = $id;

            $obj = Simplify_PaymentsApi::findObject($val, $authentication);

            return $obj;
        }


        /**
         * Updates an Simplify_Invoice object.
         *
         * The properties that can be updated:
         * <dl style="padding-left:10px;">
         *     <dt><tt>billingAddress.city</tt></dt>    <dd>Billing address city of the location where the goods or services were supplied. [max length: 255, min length: 2] </dd>
         *     <dt><tt>billingAddress.country</tt></dt>    <dd>Billing address country of the location where the goods or services were supplied. [max length: 2, min length: 2] </dd>
         *     <dt><tt>billingAddress.line1</tt></dt>    <dd>Billing address line 1 of the location where the goods or services were supplied. [max length: 255] </dd>
         *     <dt><tt>billingAddress.line2</tt></dt>    <dd>Billing address line 2 of the location where the goods or services were supplied. [max length: 255] </dd>
         *     <dt><tt>billingAddress.name</tt></dt>    <dd>Billing address name of the location where the goods or services were supplied. [max length: 255] </dd>
         *     <dt><tt>billingAddress.state</tt></dt>    <dd>Billing address state of the location where the goods or services were supplied. [max length: 255] </dd>
         *     <dt><tt>billingAddress.zip</tt></dt>    <dd>Billing address zip of the location where the goods or services were supplied. [max length: 32] </dd>
         *     <dt><tt>businessAddress.city</tt></dt>    <dd>Business address city of the business that is sending the invoice. [max length: 255, min length: 2] </dd>
         *     <dt><tt>businessAddress.country</tt></dt>    <dd>Business address country of the business that is sending the invoice. [max length: 2, min length: 2] </dd>
         *     <dt><tt>businessAddress.line1</tt></dt>    <dd>Business address line 1 of the business that is sending the invoice. [max length: 255] </dd>
         *     <dt><tt>businessAddress.line2</tt></dt>    <dd>Business address line 2 of the business that is sending the invoice. [max length: 255] </dd>
         *     <dt><tt>businessAddress.name</tt></dt>    <dd>Business address name of the business that is sending the invoice. [max length: 255] </dd>
         *     <dt><tt>businessAddress.state</tt></dt>    <dd>Business address state of the business that is sending the invoice. [max length: 255] </dd>
         *     <dt><tt>businessAddress.zip</tt></dt>    <dd>Business address zip of the business that is sending the invoice. [max length: 32] </dd>
         *     <dt><tt>currency</tt></dt>    <dd>Currency code (ISO-4217). Must match the currency associated with your account. [max length: 3, min length: 3] </dd>
         *     <dt><tt>customerTaxNo</tt></dt>    <dd>The tax number or VAT id of the person to whom the goods or services were supplied. [max length: 255] </dd>
         *     <dt><tt>datePaid</tt></dt>    <dd>This is the date the invoice was PAID in UTC millis. </dd>
         *     <dt><tt>discountRate</tt></dt>    <dd>The discount percent as a decimal e.g. 12.5.  This is used to calculate the discount amount which is subtracted from the total amount due before any tax is applied. [max length: 6] </dd>
         *     <dt><tt>dueDate</tt></dt>    <dd>The date invoice payment is due.  If a late fee is provided this will be added to the invoice total is the due date has past. </dd>
         *     <dt><tt>email</tt></dt>    <dd>The email of the customer we are invoicing.  This is optional if customer or invoiceToCopy is provided.  A new customer will be created using the the name and email. </dd>
         *     <dt><tt>invoiceId</tt></dt>    <dd>User defined invoice id. If not provided the system will generate a numeric id. [max length: 255] </dd>
         *     <dt><tt>items.amount</tt></dt>    <dd>Amount of the invoice item in the smallest unit of your currency. Example: 100 = $1.00USD [min value: -9999900, max value: 9999900] <strong>required </strong></dd>
         *     <dt><tt>items.description</tt></dt>    <dd>The description of the invoice item. [max length: 1024] </dd>
         *     <dt><tt>items.invoice</tt></dt>    <dd>The ID of the invoice this item belongs to. </dd>
         *     <dt><tt>items.product</tt></dt>    <dd>The Id of the product this item refers to. </dd>
         *     <dt><tt>items.quantity</tt></dt>    <dd>Quantity of the item.  This total amount of the invoice item is the amount * quantity. [min value: 1, max value: 999999, default: 1] </dd>
         *     <dt><tt>items.reference</tt></dt>    <dd>User defined reference field. [max length: 255] </dd>
         *     <dt><tt>items.tax</tt></dt>    <dd>The tax ID of the tax charge in the invoice item. </dd>
         *     <dt><tt>lateFee</tt></dt>    <dd>The late fee amount that will be added to the invoice total is the due date is past due.  Value provided must be in the smallest unit of your currency. Example: 100 = $1.00USD [max value: 9999900] </dd>
         *     <dt><tt>memo</tt></dt>    <dd>A memo that is displayed to the customer on the invoice payment screen. [max length: 4000] </dd>
         *     <dt><tt>name</tt></dt>    <dd>The name of the customer we are invoicing.  This is optional if customer or invoiceToCopy is provided.  A new customer will be created using the the name and email. [max length: 50, min length: 2] </dd>
         *     <dt><tt>note</tt></dt>    <dd>This field can be used to store a note that is not displayed to the customer. [max length: 4000] </dd>
         *     <dt><tt>payment</tt></dt>    <dd>The ID of the payment.  Use this ID to query the /payment API. [max length: 255] </dd>
         *     <dt><tt>reference</tt></dt>    <dd>User defined reference field. [max length: 255] </dd>
         *     <dt><tt>shippingAddress.city</tt></dt>    <dd>Address city of the location where the goods or services were supplied. [max length: 255, min length: 2] </dd>
         *     <dt><tt>shippingAddress.country</tt></dt>    <dd>Address country of the location where the goods or services were supplied. [max length: 2, min length: 2] </dd>
         *     <dt><tt>shippingAddress.line1</tt></dt>    <dd>Address line 1 of the location where the goods or services were supplied. [max length: 255] </dd>
         *     <dt><tt>shippingAddress.line2</tt></dt>    <dd>Address line 2 of the location where the goods or services were supplied. [max length: 255] </dd>
         *     <dt><tt>shippingAddress.name</tt></dt>    <dd>Address name of the location where the goods or services were supplied. [max length: 255] </dd>
         *     <dt><tt>shippingAddress.state</tt></dt>    <dd>Address state of the location where the goods or services were supplied. [max length: 255] </dd>
         *     <dt><tt>shippingAddress.zip</tt></dt>    <dd>Address zip of the location where the goods or services were supplied. [max length: 32] </dd>
         *     <dt><tt>status</tt></dt>    <dd>New status of the invoice. </dd>
         *     <dt><tt>suppliedDate</tt></dt>    <dd>The date on which the goods or services were supplied. </dd>
         *     <dt><tt>taxNo</tt></dt>    <dd>The tax number or VAT id of the person who supplied the goods or services. [max length: 255] </dd></dl>
         * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
         * @return    Invoice a Invoice object.
         */
        public function updateInvoice($authentication = null)  {

            $args = func_get_args();
            $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 1);

            $object = Simplify_PaymentsApi::updateObject($this, $authentication);
            return $object;
        }

    /**
     * @ignore
     */
    public function getClazz() {
        return "Invoice";
    }
}