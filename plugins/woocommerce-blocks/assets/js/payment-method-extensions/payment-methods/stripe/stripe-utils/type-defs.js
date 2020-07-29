/**
 * Stripe PaymentItem object
 *
 * @typedef {Object} StripePaymentItem
 *
 * @property {string}   label     The label for the payment item.
 * @property {number}   amount    The amount for the payment item (in subunits)
 * @property {boolean}  [pending] Whether or not the amount is pending update on
 *                                recalculation.
 */

/**
 * Stripe ShippingOption object
 *
 * @typedef {Object} StripeShippingOption
 *
 * @property {string} id     A unique ID for the shipping option.
 * @property {string} label  A short label for the shipping option.
 * @property {string} detail A longer description for the shipping option.
 * @property {number} amount The amount to show for the shipping option
 *                           (in subunits)
 */

/**
 * @typedef {Object} StripeShippingAddress
 *
 * @property {string}  country             Two letter country code, capitalized
 *                                         (ISO3166 alpha-2).
 * @property {Array}   addressLine         An array of address line items.
 * @property {string}  region              The most coarse subdivision of a
 *                                         country. (state etc)
 * @property {string}  city                The name of a city, town, village etc.
 * @property {string}  postalCode          The postal or ZIP code.
 * @property {string}  recipient           The name of the recipient.
 * @property {string}  phone               The phone number of the recipient.
 * @property {string}  [sortingCode]       The sorting code as used in France.
 *                                         Not present on Apple platforms.
 * @property {string}  [dependentLocality] A logical subdivision of a city.
 *                                         Not present on Apple platforms.
 */

/**
 * @typedef {Object} StripeBillingDetails
 *
 * @property {Object} address             The billing address
 * @property {string} address.city        The billing address city
 * @property {string} address.country     The billing address country
 * @property {string} address.line1       The first line for the address
 * @property {string} address.line2       The second line fro the address
 * @property {string} address.postal_code The postal/zip code
 * @property {string} address.state       The state
 * @property {string} email               The billing email
 * @property {string} name                The billing name
 * @property {string} phone               The billing phone
 * @property {Object} [verified_address]  The verified address of the owner.
 * @property {string} [verified_email]    Provided by the payment provider.
 * @property {string} [verified_phone]    Provided by the payment provider.
 * @property {string} [verified_name]     Provided by the payment provider.
 */

/**
 * @typedef {Object} StripeBillingCard
 *
 * @property {string} brand                            The card brand
 * @property {Object} checks                           Various security checks
 * @property {string} checks.address_line1_check       If an address line1 was
 *                                                     provided, results of the
 *                                                     check.
 * @property {string} checks.address_postal_code_check If a postal code was
 *                                                     provided, results of the
 *                                                     check.
 * @property {string} checks.cvc_check                 If CVC provided, results
 *                                                     of the check.
 * @property {string} country                          Two-letter ISO code for
 *                                                     the country on the card.
 * @property {number} exp_month                        Two-digit number for
 *                                                     card expiry month.
 * @property {number} exp_year                         Two-digit number for
 *                                                     card expiry year.
 * @property {string} fingerprint                      Uniquely identifies this
 *                                                     particular card number
 * @property {string} funding                          The card funding type
 * @property {Object} generated_from                   Details of the original
 *                                                     PaymentMethod that
 *                                                     created this object.
 * @property {string} last4                            The last 4 digits of the
 *                                                     card
 * @property {Object} three_d_secure_usage             Contains details on how
 *                                                     this card may be used for
 *                                                     3d secure
 * @property {Object} wallet                           If this card is part of a
 *                                                     card wallet, this
 *                                                     contains the details of
 *                                                     the card wallet.
 */

/**
 * @typedef {Object} StripePaymentMethod
 *
 * @property {string}               id               Unique identifier for the
 *                                                   object
 * @property {StripeBillingDetails} billing_details  The billing details for the
 *                                                   payment method
 * @property {StripeBillingCard}    card             Details on the card used to
 *                                                   pay
 * @property {string}               customer         The ID of the customer to
 *                                                   which this payment method
 *                                                   is saved.
 * @property {Object}               metadata         Set of key-value pairs that
 *                                                   can be attached to the
 *                                                   object.
 * @property {string}               type             Type of payment method
 * @property {string}               object           The type of object. Always
 *                                                   'payment_method'. Can use
 *                                                   to validate!
 * @property {Object}               card_present     If this is a card present
 *                                                   payment method, contains
 *                                                   details about that card
 * @property {number}               created          The timestamp for when the
 *                                                   card was created.
 * @property {Object}               fpx              If this is an fpx payment
 *                                                   method, contains details
 *                                                   about it.
 * @property {Object}               ideal            If this is an ideal payment
 *                                                   method, contains details
 *                                                   about it.
 * @property {boolean}              livemode         True if the object exists
 *                                                   in live mode or if in test
 *                                                   mode.
 * @property {Object}               sepa_debit       If this is a sepa_debit
 *                                                   payment method, contains
 *                                                   details about it.
 */

/**
 * @typedef {Object} StripeSource
 *
 * @property {string}               id                    Unique identifier for
 *                                                        object
 * @property {number}               amount                A positive number in
 *                                                        the smallest currency
 *                                                        unit.
 * @property {string}               currency              The three-letter ISO
 *                                                        code for the currency
 * @property {string}               customer              The ID of the customer
 *                                                        to which this source
 *                                                        is attached.
 * @property {Object}               metadata              Arbitrary key-value
 *                                                        pairs that can be
 *                                                        attached.
 * @property {StripeBillingDetails} owner                 Information about the
 *                                                        owner of the payment
 *                                                        made.
 * @property {Object}               [redirect]            Information related to
 *                                                        the redirect flow
 *                                                        (present if the source
 *                                                        is authenticated by
 *                                                        redirect)
 * @property {string}               statement_descriptor  Extra information
 *                                                        about a source (will
 *                                                        appear on customer's
 *                                                        statement)
 * @property {string}               status                The status of the
 *                                                        source.
 * @property {string}               type                  The type of the source
 *                                                        (it is a payment
 *                                                        method type)
 * @property {string}               object                Value is "source" can
 *                                                        be used to validate.
 * @property {string}               client_secret         The client secret of
 *                                                        the source. Used for
 *                                                        client-side retrieval
 *                                                        using a publishable
 *                                                        key.
 * @property {Object}               [code_verification]   Information related to
 *                                                        the code verification
 *                                                        flow.
 * @property {number}               created               When the source object
 *                                                        was instantiated
 *                                                        (timestamp).
 * @property {string}               flow                  The authentication
 *                                                        flow of the source.
 * @property {boolean}              livemode              If true then payment
 *                                                        is made in live mode
 *                                                        otherwise test mode.
 * @property {Object}               [receiver]            Information related to
 *                                                        the receiver flow.
 * @property {Object}               source_order          Information about the
 *                                                        items and shipping
 *                                                        associated with the
 *                                                        source.
 * @property {string}               usage                 Whether source should
 *                                                        be reusable or not.
 */

/**
 * @typedef {Object} StripePaymentResponse
 *
 * @property {Object}                 token             A stripe token object
 * @property {StripePaymentMethod}    paymentMethod     The stripe payment method
 *                                                      object
 * @property {?StripeSource}          source            Present if this was the
 *                                                      result of a source event
 *                                                      listener
 * @property {Function}               complete          Call this when the token
 *                                                      data has been processed.
 * @property {string}                 [payerName]       The customer's name.
 * @property {string}                 [payerEmail]      The customer's email.
 * @property {string}                 [payerPhone]      The customer's phone.
 * @property {StripeShippingAddress}  [shippingAddress] The final shipping
 *                                                      address the customer
 *                                                      indicated
 * @property {StripeShippingOption}   [shippingOption]  The final shipping
 *                                                      option the customer
 *                                                      selected.
 * @property {string}                 methodName        The unique name of the
 *                                                      payment handler the
 *                                                      customer chose to
 *                                                      authorize payment
 */

/**
 * @typedef {Object} StripePaymentRequestOptions The configuration of stripe
 *                                               payment request options to
 *                                               pass in.
 *
 * @property {string}                 country           Two-letter (ISO)
 *                                                      country code.
 * @property {string}                 currency          Three letter currency
 *                                                      code.
 * @property {StripePaymentItem}      total             Shown to the customer.
 * @property {StripePaymentItem[]}    displayItems      Line items shown to the
 *                                                      customer.
 * @property {boolean}                requestPayerName  Whether or not to
 *                                                      collect the payer's
 *                                                      name.
 * @property {boolean}                requestPayerEmail Whether or not to
 *                                                      collect the payer's
 *                                                      email.
 * @property {boolean}                requestPayerPhone Whether or not to
 *                                                      collect the payer's
 *                                                      phone.
 * @property {boolean}                requestShipping   Whether to collect
 *                                                      shipping address.
 * @property {StripeShippingOption[]} shippingOptions   Available shipping
 *                                                      options.
 */

/**
 * @typedef {Object} StripePaymentRequest Stripe payment request object.
 *
 * @property {function():Promise} canMakePayment Returns a promise that resolves
 *                                               with an object detailing if a
 *                                               browser payment API is
 *                                               available.
 * @property {function()}         show           Shows the browser's payment
 *                                               interface (called automatically
 *                                               if payment request button in
 *                                               use)
 * @property {function()}         update         Used to update a PaymentRequest
 *                                               object.
 * @property {function()}         on             For registering callbacks on
 *                                               payment request events.
 */

/**
 * @typedef {Object} Stripe Stripe api object.
 */

/**
 * @typedef {Object} CreditCardIcon
 *
 * @property {string} url  Url to icon.
 * @property {string} alt  Alt text for icon.
 */

/**
 * @typedef {Object} StripeServerData
 *
 * @property {string}                      stripeTotalLabel     The string used for payment
 *                                                              descriptor.
 * @property {string}                      publicKey            The public api key for stripe
 *                                                              requests.
 * @property {boolean}                     allowPrepaidCard     True means that prepaid cards
 *                                                              can be used for payment.
 * @property {Object}                      button               Contains button styles
 * @property {string}                      button.type          The type of button.
 * @property {string}                      button.theme         The theme for the button.
 * @property {string}                      button.height        The height (in pixels) for
 *                                                              the button.
 * @property {string}                      button.locale        The locale to use for stripe
 *                                                              elements.
 * @property {boolean}                     inline_cc_form       Whether stripe cc should use
 *                                                              inline cc
 *                                                              form or separate inputs.
 * @property {{[k:string]:CreditCardIcon}} icons                Contains supported cc icons.
 * @property {boolean}                     allowSavedCards      Used to indicate whether saved cards
 *                                                              can be used.
 * @property {boolean}                     allowPaymentRequest  True if merchant has enabled payment
 *                                                              request (Chrome/Apple Pay).
 */

/**
 * @typedef {Object} StripeElementOptions
 *
 * @property {Object}            options  The configuration object for stripe
 *                                        elements.
 * @property {function(boolean)} onActive A callback for setting whether an
 *                                        element is active or not. "Active"
 *                                        means it's not empty.
 * @property {string}            error    Any error message from the stripe
 *                                        element.
 * @property {function(string)}  setError A callback for setting an error
 *                                        message.
 */

export {};
