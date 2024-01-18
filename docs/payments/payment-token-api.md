---
post_title: WooCommerce Payment Token API
menu_title: Payment Token API
tags: reference
---

WooCommerce 2.6 introduced an API for storing and managing payment tokens for gateways. Users can also manage these tokens from their account settings and choose from saved payment tokens on checkout.

This guide offers a few useful tutorials for using the new API as well as all the various methods available to you.

## Tutorials

### Adding Payment Token API Support To Your Gateway

We'll use the Simplify Commerce gateway in some of these examples.

#### Step 0: Extending The Correct Gateway Base

WooCommerce ships with two base classes for gateways. These classes were introduced along with the Token API in 2.6. They  are `WC_Payment_Gateway_CC` (for credit card based tokens) and `WC_Payment_Gateway_eCheck` (for eCheck based tokens). They contain some useful code for generating payment forms on checkout and should hopefully cover most cases.

You can also implement your own gateway base by extending the abstract `WC_Payment_Gateway` class, if neither of those classes work for you.

Since Simplify deals with credit cards, we extend the credit card gateway.

`class WC_Gateway_Simplify_Commerce extends WC_Payment_Gateway_CC`

#### Step 1: 'Supports' Array

We need to tell WooCommerce our gateway supports tokenization. Like other gateways features, this is defined in a gateway's `__construct` in an array called `supports`.

Here is the Simplify array:

```php
$this->supports = array(
    'subscriptions',
    'products',
    ...
    'refunds',
    'pre-orders',
);
```

Add `tokenization` to this array.

#### Step 2: Define A Method For Adding/Saving New Payment Methods From "My Account"

The form handler that is run when adding a new payment method from the "my accounts" section will call your gateway's `add_payment_method` method.

After any validation (i.e. making sure the token and data you need is present from the payment provider), you can start building a new token by creating an instance of one of the following classes: `WC_Payment_Token_CC` or `WC_Payment_Token_eCheck`. Like gateways, you can also extend the abstract `WC_Payment_Token` class and define your own token type type if necessary. For more information on all three of these classes and their methods, see further down below in this doc.

Since Simplify uses credit cards, we will use the credit card class.

`$token = new WC_Payment_Token_CC();`

We will use various `set_` methods to pass in information about our token. To start with we will pass the token string and the gateway ID so the token can be associated with Simplify.

```php
$token->set_token( $token_string );
$token->set_gateway_id( $this->id ); // `$this->id` references the gateway ID set in `__construct`
```

At this point we can set any other necessary information we want to store with the token. Credit cards require a card type (visa, mastercard, etc), last four digits of the card number, an expiration month, and an expiration year.

```php
$token->set_card_type( 'visa' );
$token->set_last4( '1234' );
$token->set_expiry_month( '12' );
$token->set_expiry_year( '2018' );
```

In most cases, you will also want to associate a token with a specific user:

`$token->set_user_id( get_current_user_id() );`

Finally, we can save our token to the database once the token object is built.

`$token->save();`

Save will return `true` if the token was successfully saved, and `false` if an error occurred (like a missing field).

#### Step 3: Save Methods On Checkout

WooCommerce also allows customers to save a new payment token during the checkout process in addition to "my account". You'll need to add some code to your gateways `process_payment` function to make this work correctly.

To figure out if you need to save a new payment method you can check the following POST field which should return `true` if the "Save to Account" checkbox was selected.

`wc-{$gateway_id}-new-payment-method`

If you have previously saved tokens being offered to the user, you can also look at `wc-{$gateway_id}-payment-token` for the value `new` to make sure the "Use a new card" / "Use new payment method" radio button was selected.

Once you have found out that a token should be saved you can save a token in the same way you did in Step 2, using the `set_` and `save` methods.

#### Step 4: Retrieve The Token When Processing Payments

You will need to retrieve a saved token when processing a payment in your gateway if a user selects one. This should also be done in your `process_payment` method.

You can check if an existing token should be used with a conditional like the following:

`if ( isset( $_POST['wc-simplify_commerce-payment-token'] ) && 'new' !== $_POST['wc-simplify_commerce-payment-token'] ) {`

`wc-{$gateway_id}}-payment-token` will return the ID of the selected token.

You can then load a token from ta ID (more on the WC_Payment_Tokens class later in this doc):

```php
$token_id = wc_clean( $_POST['wc-simplify_commerce-payment-token'] );
$token    = WC_Payment_Tokens::get( $token_id );
```

This does **not** check if the loaded token belongs to the current user. You can do that with a simple check:

```php
// Token user ID does not match the current user... bail out of payment processing.
if ( $token->get_user_id() !== get_current_user_id() ) {
    // Optionally display a notice with `wc_add_notice`
    return;
}
```

Once you have loaded the token and done any necessary checks, you can get the actual token string (to pass to your payment provider) by using
`$token->get_token()`.

### Creating A New Token Type

You can extend the abstract WC_Payment_Token class and create a new token type If the provided eCheck and CC token types do not satisfy your requirements. There are a few things you need to include if you do this.

#### Step 0: Extend WC_Payment_Token And Name Your Type

Start by extending WC_Payment_Token and providing a name for the new type. We'll look at how the eCheck token class is built since it is the most basic token type shipped in WooCommerce core.

A barebones token file should look like this:

```php
class WC_Payment_Token_eCheck extends WC_Payment_Token {

    /** @protected string Token Type String */
    protected $type = 'eCheck';

}
```

The name for this token type is 'eCheck'. The value provided in `$type` needs to match the class name (i.e: `WC_Payment_Token_$type`).

#### Step 1: Provide A Validate Method

Some basic validation is performed on a token before it is saved to the database. `WC_Payment_Token` checks to make sure the actual token value is set, as well as the `$type` defined above. If you want to validate the existence of other data (eChecks require the last 4 digits for example) or length (an expiry month should be 2 characters), you can provide your own `validate()` method.

Validate should return `true` if everything looks OK, and false if something doesn't.

Always make sure to call `WC_Payment_Token`'s validate method before adding in your own logic.

```php
public function validate() {
    if ( false === parent::validate() ) {
	       return false;
	}
```

Now we can add some logic in for the "last 4" digits.

```php
if ( ! $this->get_last4() ) {
    return false;
}
```

Finally, return true if we make it to the end of the `validate()` method.

```php
    return true;
}
```

#### Step 2: Provide get\_ And set\_ Methods For Extra Data

You can now add your own methods for each piece of data you would like to expose. Handy functions are provided to you to make storing and retrieving data easy. All data is stored in a meta table so you do not need to make your own table or add new fields to an existing one.

Provide a `get_` and `set_` method for each piece of data you want to capture. For eChecks, this is "last4" for the last 4 digits of a check.

```php
public function get_last4() {
    return $this->get_meta( 'last4' );
}

public function set_last4( $last4 ) {
    $this->add_meta_data( 'last4', $last4, true );
}
```

That's it! These meta functions are provided by [WC_Data](https://github.com/woothemes/woocommerce/blob/trunk/includes/abstracts/abstract-wc-data.php).

#### Step 3: Use Your New Token Type

You can now use your new token type, either directly when building a new token

```php
`$token = new WC_Payment_Token_eCheck();`
// set token properties
$token->save()
```

or it will be returned when using `WC_Payment_Tokens::get( $token_id )`.

## Classes

### WC_Payment_Tokens

This class provides a set of helpful methods for interacting with payment tokens. All methods are static and can be called without creating an instance of the class.

#### get_customer_tokens( $customer_id, $gateway_id = '' )

Returns an array of token objects for the customer specified in `$customer_id`. You can filter by gateway by providing a gateway ID as well.

```php
// Get all tokens for the current user
$tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id() );
// Get all tokens for user 42
$tokens = WC_Payment_Tokens::get_customer_tokens( 42 );
// Get all Simplify tokens for the current user
$tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id(), 'simplify_commerce' );
```

#### get_customer_default_token( $customer_id )

Returns a token object for the token that is marked as 'default' (the token that will be automatically selected on checkout). If a user does not have a default token/has no tokens, this function will return null.

```php
// Get default token for the current user
$token = WC_Payment_Tokens::get_customer_default_token( get_current_user_id() );
// Get default token for user 520
$token = WC_Payment_Tokens::get_customer_default_token( 520 );
```

#### get_order_tokens( $order_id )

Orders can have payment tokens associated with them (useful for subscription products and renewing, for example). You can get a list of tokens associated with this function. Alternatively you can use `WC_Order`'s '`get_payment_tokens()` function to get the same result.

```php
// Get tokens associated with order 25
$tokens = WC_Payment_Tokens::get_order_tokens( 25 );
// Get tokens associated with order 25, via WC_Order
$order = wc_get_order( 25 );
$tokens =  $order->get_payment_tokens();
```

#### get( $token_id )

Returns a single payment token object for the provided `$token_id`.

```php
// Get payment token 52
$token = WC_Payment_Tokens::get( 52 );
```

#### delete( $token_id )

Deletes the provided token.

```php
// Delete payment token 52
WC_Payment_Tokens::delete( 52 );
```

#### set_users_default( $user_id, $token_id )

Makes the provided token (`$token_id`) the provided user (`$user_id`)'s default token. It makes sure that whatever token is currently set is default is removed and sets the new one.

```php
// Set user 17's default token to token 82
WC_Payment_Tokens::set_users_default( 17, 82 );
```

#### get_token_type_by_id( $token_id )

You can use this function If you have a token's ID but you don't know what type of token it is (credit card, eCheck, ...).

```php
// Find out that payment token 23 is a cc/credit card token
$type = WC_Payment_Tokens::get_token_type_by_id( 23 );
```

### WC_Payment_Token_CC

`set_` methods **do not** update the token in the database. You must call `save()`, `create()` (new tokens only), or `update()` (existing tokens only).

#### validate()

Makes sure the credit card token has the last 4 digits stored, an expiration year in the format YYYY, an expiration month with the format MM, the card type, and the actual token.

```php
$token = new WC_Payment_Token_CC();
$token->set_token( 'token here' );
$token->set_last4( '4124' );
$token->set_expiry_year( '2017' );
$token->set_expiry_month( '1' ); // incorrect length
$token->set_card_type( 'visa' );
var_dump( $token->validate() ); // bool(false)
$token->set_expiry_month( '01' );
var_dump( $token->validate() ); // bool(true)
```

#### get_card_type()

Get the card type (visa, mastercard, etc).

```php
$token = WC_Payment_Tokens::get( 42 );
echo $token->get_card_type();
```

#### set_card_type( $type )

Set the credit card type. This is a freeform text field, but the following values can be used and WooCommerce will show a formatted label New labels can be added with the `wocommerce_credit_card_type_labels` filter.

```php
$token = WC_Payment_Tokens::get( 42 );
$token->set_last4( 'visa' );
echo $token->get_card_type(); // returns visa
```

Supported types/labels:

```php
array(
	'mastercard'       => __( 'MasterCard', 'woocommerce' ),
	'visa'             => __( 'Visa', 'woocommerce' ),
	'discover'         => __( 'Discover', 'woocommerce' ),
	'american express' => __( 'American Express', 'woocommerce' ),
	'diners'           => __( 'Diners', 'woocommerce' ),
	'jcb'              => __( 'JCB', 'woocommerce' ),
) );
```

#### get_expiry_year()

Get the card's expiration year.

```php
$token = WC_Payment_Tokens::get( 42 );
echo $token->get_expiry_year;
```

#### set_expiry_year( $year )

Set the card's expiration year. YYYY format.

```php
$token = WC_Payment_Tokens::get( 42 );
$token->set_expiry_year( '2018' );
echo $token->get_expiry_year(); // returns 2018
```

#### get_expiry_month()

Get the card's expiration month.

```php
$token = WC_Payment_Tokens::get( 42 );
echo $token->get_expiry_month();
```

#### set_expiry_month( $month )

Set the card's expiration month. MM format.

```php
$token = WC_Payment_Tokens::get( 42 );
$token->set_expiry_year( '12' );
echo $token->get_expiry_month(); // returns 12
```

#### get_last4()

Get the last 4 digits of the stored credit card number.

```php
$token = WC_Payment_Tokens::get( 42 );
echo $token->get_last4();
```

#### set_last4( $last4 )

Set the last 4 digits of the stored credit card number.

```php
$token = WC_Payment_Tokens::get( 42 );
$token->set_last4( '2929' );
echo $token->get_last4(); // returns 2929
```

### WC_Payment_Token_eCheck

`set_` methods **do not** update the token in the database. You must call `save()`, `create()` (new tokens only), or `update()` (existing tokens only).

#### validate()

Makes sure the eCheck token has the last 4 digits stored as well as the actual token.

```php
$token = new WC_Payment_Token_eCheck();
$token->set_token( 'token here' );
var_dump( $token->validate() ); // bool(false)
$token->set_last4( '4123' );
var_dump( $token->validate() ); // bool(true)
```

#### get_last4()

Get the last 4 digits of the stored account number.

```php
$token = WC_Payment_Tokens::get( 42 );
echo $token->get_last4();
```

#### set_last4( $last4 )

Set the last 4 digits of the stored credit card number.

```php
$token = WC_Payment_Tokens::get( 42 );
$token->set_last4( '2929' );
echo $token->get_last4(); // returns 2929
```

### WC_Payment_Token

You should not use `WC_Payment_Token` directly. Use one of the bundled token classes (`WC_Payment_Token_CC` for credit cards and `WC_Payment_Token_eCheck`). You can extend this class if neither of those work for you. All the methods defined in this section are available to those classes.

`set_` methods **do not** update the token in the database. You must call `save()`, `create()` (new tokens only), or `update()` (existing tokens only).

#### get_id()

Get the token's ID.

```php
// Get the token ID for user ID 26's default token
$token = WC_Payment_Tokens::get_customer_default_token( 26 );
echo $token->get_id();
```

#### get_token()

Get the actual token string (used to communicate with payment processors).

```php
$token = WC_Payment_Tokens::get( 49 );
echo $token->get_token();
```

#### set_token( $token )

Set the token string.

```php
// $api_token comes from an API request to a payment processor.
$token = WC_Payment_Tokens::get( 42 );
$token->set_token( $api_token );
echo $token->get_token(); // returns our token
```

#### get_type()

Get the type of token. CC or eCheck. This will also return any new types introduced.

```php
$token = WC_Payment_Tokens::get( 49 );
echo $token->get_type();
```

#### get_user_id()

Get the user ID associated with the token.

```php
$token = WC_Payment_Tokens::get( 49 );
if ( $token->get_user_id() === get_current_user_id() ) {
    // This token belongs to the current user.
}
```

#### set_user_id( $user_id )

Associate a token with a user.

```php
$token = WC_Payment_Tokens::get( 42 );
$token->set_user_id( '21' ); // This token now belongs to user 21.
echo $token->get_last4(); // returns 2929
```

#### get_gateway_id

Get the gateway associated with the token.

```php
$token = WC_Payment_Tokens::get( 49 );
$token->get_gateway_id();
```

#### set_gateway_id( $gateway_id )

Set the gateway associated with the token. This should match the "ID" defined in your gateway. For example, 'simplify_commerce' is the ID for core's implementation of Simplify.

```php
$token->set_gateway_id( 'simplify_commerce' );
echo $token->get_gateway_id();
```

#### is_default()

Returns true if the token is marked as a user's default. Default tokens are auto-selected on checkout.

```php
$token = WC_Payment_Tokens::get( 42 ); // Token 42 is a default token for user 3
var_dump( $token->is_default() ); // returns true
$token = WC_Payment_Tokens::get( 43 ); // Token 43 is user 3's token, but not default
var_dump( $token->is_default() ); // returns false
```

#### set_default( $is_default )

Toggle a tokens 'default' flag. Pass true to set it as default, false if its just another token. This **does not** unset any other tokens that may be set as default. You can use `WC_Payment_Tokens::set_users_default()` to handle that instead.

```php
$token = WC_Payment_Tokens::get( 42 ); // Token 42 is a default token for user 3
var_dump( $token->is_default() ); // returns true
$token->set_default( false );
var_dump( $token->is_default() ); // returns false
```

#### validate()

Does a check to make sure both the token and token type (CC, eCheck, ...) are present. See `WC_Payment_Token_CC::validate()` or `WC_Payment_Token_eCheck::validate()` for usage.

#### read( $token_id )

Load an existing token object from the database. See `WC_Payment_Tokens::get()` which is an alias of this function.

```php
// Load a credit card toke, ID 55, user ID 5
$token = WC_Payment_Token_CC();
$token->read( 55 );
echo $token->get_id(); // returns 55
echo $token->get_user_id(); // returns 5
```

#### update()

Update an existing token. This will take any changed fields (`set_` functions) and actually save them to the database. Returns true or false depending on success.

```php
$token = WC_Payment_Tokens::get( 42 ); // credit card token
$token->set_expiry_year( '2020' );
$token->set_expiry_month( '06 ');
$token->update();
```

#### create()

This will create a new token in the database. So once you build it, create() will create a new token in the database with the details. Returns true or false depending on success.

```php
$token = new WC_Payment_Token_CC();
// set last4, expiry year, month, and card type
$token->create(); // save to database
```

#### save()

`save()` can be used in place of `update()` and `create()`. If you are working with an existing token, `save()` will call `update()`. A new token will call `create()`. Returns true or false depending on success.

```php
// calls update
$token = WC_Payment_Tokens::get( 42 ); // credit card token
$token->set_expiry_year( '2020' );
$token->set_expiry_month( '06 ');
$token->save();
// calls create
$token = new WC_Payment_Token_CC();
// set last4, expiry year, month, and card type
$token->save();
```

#### delete()

Deletes a token from the database.

```php
$token = WC_Payment_Tokens::get( 42 );
$token->delete();
```
