/**
 * External dependencies
 */
import { expect, request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { setOption } from '../../utils/options';
import { logIn } from '../../utils/login';
import { customer } from '../../test-data/data';
import ApiClient, { WC_API_PATH } from '../../utils/api-client';

const now = Date.now();
const productName = `Out of stock product test ${ now }`;
const productPrice = '13.99';

class AcceptanceHelper {
	constructor( baseURL, page ) {
		this.page = page;
		this.baseURL = baseURL;
		this.api = ApiClient.getInstance();
	}
	given = {
		iAmViewingThePageOfASimpleProductThatIsOutOfStock:
			this.iAmViewingThePageOfASimpleProductThatIsOutOfStock.bind( this ),
		iGoToTheProductPage: this.iGoToTheProductPage.bind( this ),
		iAmViewingThePageOfAVariableProductThatContainsOutOfStockVariations:
			this.iAmViewingThePageOfAVariableProductThatContainsOutOfStockVariations.bind(
				this
			),
		iAmViewingThePageOfAVariableProductThatContainsOutOfStockVariationsWithAnAttributeWithValueAny:
			this.iAmViewingThePageOfAVariableProductThatContainsOutOfStockVariationsWithAnAttributeWithValueAny.bind(
				this
			),
		signUpsAreLimitedToLoggedInUsers:
			this.signUpsAreLimitedToLoggedInUsers.bind( this ),
		signUpsAreSingleOptInWithoutCheckbox:
			this.signUpsAreSingleOptInWithoutCheckbox.bind( this ),
		signUpsAreSingleOptInWithCheckbox:
			this.signUpsAreSingleOptInWithCheckbox.bind( this ),
		signUpsAreDoubleOptIn: this.signUpsAreDoubleOptIn.bind( this ),
		signUpsAreSingleOptInAndANewAccountIsCreatedOnSignUp:
			this.signUpsAreSingleOptInAndANewAccountIsCreatedOnSignUp.bind(
				this
			),
		signUpsAreDoubleOptInAndANewAccountIsCreatedOnSignUp:
			this.signUpsAreDoubleOptInAndANewAccountIsCreatedOnSignUp.bind(
				this
			),
		numberOfCustomerWhoHaveJoinedTheWaitlistIsVisible:
			this.numberOfCustomerWhoHaveJoinedTheWaitlistIsVisible.bind( this ),
		aSimpleProductThatIsOutOfStock:
			this.aSimpleProductThatIsOutOfStock.bind( this ),
		aVariableProductThatContainsOutOfStockVariations:
			this.aVariableProductThatContainsOutOfStockVariations.bind( this ),
		theProductHasNotifications:
			this.theProductHasNotifications.bind( this ),
		theVariationHasNotifications:
			this.theVariationHasNotifications.bind( this ),
		aVariableProductThatContainsOutOfStockVariationsWithAnAttributeWithValueAny:
			this.aVariableProductThatContainsOutOfStockVariationsWithAnAttributeWithValueAny.bind(
				this
			),
		signUpPromptsInCatalogAreEnabled:
			this.signUpPromptsInCatalogAreEnabled.bind( this ),
		iAmOnTheCatalogPage: this.iAmOnTheCatalogPage.bind( this ),
		iSeeAPromptToSignUpToBeNotifiedWhenTheProductIsBackInStock:
			this.iSeeAPromptToSignUpToBeNotifiedWhenTheProductIsBackInStock.bind(
				this
			),
		aVariableProductWhoseVariationsAreAllOutOfStock:
			this.aVariableProductWhoseVariationsAreAllOutOfStock.bind( this ),
	};
	when = {
		iClickTheNotifyMeButton: this.iClickTheNotifyMeButton.bind( this ),
		iChooseAVariationThatIsInStock:
			this.iChooseAVariationThatIsInStock.bind( this ),
		iChooseAVariationThatIsOutOfStock:
			this.iChooseAVariationThatIsOutOfStock.bind( this ),
		iLogInToMyAccount: this.iLogInToMyAccount.bind( this ),
		iEnterMyEmail: this.iEnterMyEmail.bind( this ),
		iTickTheCheckbox: this.iTickTheCheckbox.bind( this ),
		iAmViewingThePageOfASimpleProductThatIsOutOfStock:
			this.iAmViewingThePageOfASimpleProductThatIsOutOfStock.bind( this ),
		iGoToTheProductPage: this.iGoToTheProductPage.bind( this ),
		iViewTheConfirmationIReceivedViaEmail:
			this.iViewTheConfirmationIReceivedViaEmail.bind( this ),
		iFollowTheLink: this.iFollowTheLink.bind( this ),
		iViewTheDoubleOptInVerificationIReceivedViaEmail:
			this.iViewTheDoubleOptInVerificationIReceivedViaEmail.bind( this ),
		iFollowTheConfirmLink: this.iFollowTheConfirmLink.bind( this ),
		iViewTheNotificationIReceivedViaEmail:
			this.iViewTheNotificationIReceivedViaEmail.bind( this ),
		iClickTheButtonPromptingMeToPurchaseTheProduct:
			this.iClickTheButtonPromptingMeToPurchaseTheProduct.bind( this ),
		iAmOnTheStockNotificationsAccountPage:
			this.iAmOnTheStockNotificationsAccountPage.bind( this ),
		iCancelAnActiveNotification:
			this.iCancelAnActiveNotification.bind( this ),
		iReSendAVerificationEmail: this.iReSendAVerificationEmail.bind( this ),
		iCancelAPendingNotification:
			this.iCancelAPendingNotification.bind( this ),
		iFollowTheSignUpPromptLink:
			this.iFollowTheSignUpPromptLink.bind( this ),
		iReloadThePage: this.iReloadThePage.bind( this ),
	};
	then = {
		iSeeAPromptToSignUpAndBeNotifiedWhenTheProductIsBackInStock:
			this.iSeeAPromptToSignUpAndBeNotifiedWhenTheProductIsBackInStock.bind(
				this
			),
		iDontSeeAFieldToEnterMyEmail:
			this.iDontSeeAFieldToEnterMyEmail.bind( this ),
		iDontSeeAnOptInCheckbox: this.iDontSeeAnOptInCheckbox.bind( this ),
		iSeeThatMySignupRequestWasSuccessful:
			this.iSeeThatMySignupRequestWasSuccessful.bind( this ),
		iDontSeeASignUpButton: this.iDontSeeASignUpButton.bind( this ),
		iSeeASignUpButton: this.iSeeASignUpButton.bind( this ),
		iSeeAPromptToManageMyNotifications:
			this.iSeeAPromptToManageMyNotifications.bind( this ),
		iSeeThatIAlreadyJoinedTheWaitlist:
			this.iSeeThatIAlreadyJoinedTheWaitlist.bind( this ),
		iDontSeeAPromptToSignUpAndBeNotifiedWhenTheProductIsBackInStock:
			this.iDontSeeAPromptToSignUpAndBeNotifiedWhenTheProductIsBackInStock.bind(
				this
			),
		iSeeAFieldToEnterMyEmail: this.iSeeAFieldToEnterMyEmail.bind( this ),
		iSeeAPromptToLogInToMyAccount:
			this.iSeeAPromptToLogInToMyAccount.bind( this ),
		iSeeAnOptInCheckbox: this.iSeeAnOptInCheckbox.bind( this ),
		iAmPromptedToCheckMyEmail: this.iAmPromptedToCheckMyEmail.bind( this ),
		iSeeThatSomeCustomersHaveAlreadySignedUp:
			this.iSeeThatSomeCustomersHaveAlreadySignedUp.bind( this ),
		iSeeSomeDetailsAboutTheProductISubscribedTo:
			this.iSeeSomeDetailsAboutTheProductISubscribedTo.bind( this ),
		iSeeSomeDetailsAboutTheVariationProductISubscribedTo:
			this.iSeeSomeDetailsAboutTheVariationProductISubscribedTo.bind(
				this
			),
		iSeeALinkToCancelMyRequest:
			this.iSeeALinkToCancelMyRequest.bind( this ),
		iSeeThatMyRequestWasCancelled:
			this.iSeeThatMyRequestWasCancelled.bind( this ),
		iAmPromptedToVerifyMyRequest:
			this.iAmPromptedToVerifyMyRequest.bind( this ),
		iSeeALinkToVerifyMyRequest:
			this.iSeeALinkToVerifyMyRequest.bind( this ),
		iSeeTheTitleOfTheVerificationEmail:
			this.iSeeTheTitleOfTheVerificationEmail.bind( this ),
		iSeeAMessageThatMyConfirmRequestWasSuccessful:
			this.iSeeAMessageThatMyConfirmRequestWasSuccessful.bind( this ),
		iCanSeeAConfirmationEmailWithDetailsAboutTheVariationProductISubscribedTo:
			this.iCanSeeAConfirmationEmailWithDetailsAboutTheVariationProductISubscribedTo.bind(
				this
			),
		iCanSeeAConfirmationEmailWithDetailsAboutTheProductISubscribedTo:
			this.iCanSeeAConfirmationEmailWithDetailsAboutTheProductISubscribedTo.bind(
				this
			),
		iAmTakenToTheProductPageToCompleteMyPurchase:
			this.iAmTakenToTheProductPageToCompleteMyPurchase.bind( this ),
		iSeeThatTheVariationIHadSignedUpForIsPreSelected:
			this.iSeeThatTheVariationIHadSignedUpForIsPreSelected.bind( this ),
		iSeeAnActiveNotificationsTable:
			this.iSeeAnActiveNotificationsTable.bind( this ),
		iSeeANotificationForTheProductIHaveSubscribedTo:
			this.iSeeANotificationForTheProductIHaveSubscribedTo.bind( this ),
		iSeeThatICanCancelActiveNotifications:
			this.iSeeThatICanCancelActiveNotifications.bind( this ),
		iSeeThatTheNotificationWasCancelled:
			this.iSeeThatTheNotificationWasCancelled.bind( this ),
		iSeeThatICanReSendVerificationEmails:
			this.iSeeThatICanReSendVerificationEmails.bind( this ),
		iSeeThatTheEmailWasResentSuccessfully:
			this.iSeeThatTheEmailWasResentSuccessfully.bind( this ),
		iSeeAPendingNotificationsTable:
			this.iSeeAPendingNotificationsTable.bind( this ),
		iSeeAPendingNotificationForTheProductIHaveSubscribedTo:
			this.iSeeAPendingNotificationForTheProductIHaveSubscribedTo.bind(
				this
			),
		iSeeThatICanCancelPendingNotifications:
			this.iSeeThatICanCancelPendingNotifications.bind( this ),
		iSeeThatThePendingNotificationWasCancelled:
			this.iSeeThatThePendingNotificationWasCancelled.bind( this ),
		iSeeSomeActivityRelatedWithNotificationsISignedUpToReceiveInThePast:
			this.iSeeSomeActivityRelatedWithNotificationsISignedUpToReceiveInThePast.bind(
				this
			),
		iSeeANoticeWithFurtherInstructions:
			this.iSeeANoticeWithFurtherInstructions.bind( this ),
		iCompleteTheSignUpProcess: this.iCompleteTheSignUpProcess.bind( this ),
	};

	async iReloadThePage() {
		await this.page.reload();
	}

	async aVariableProductWhoseVariationsAreAllOutOfStock() {
		await this.api
			.post( `${ WC_API_PATH }/products`, {
				name: `A Variable Product ${ now }`,
				type: 'variable',
				attributes: [
					{
						name: 'Colour',
						visible: true,
						variation: true,
						options: [ 'Red', 'Green', 'Blue', 'White' ],
					},
				],
			} )
			.then( ( response ) => {
				this.productData = response.data;
			} );
		await this.api.post(
			`${ WC_API_PATH }/products/${ this.productData.id }/variations`,
			{
				regular_price: '1.00',
				stock_status: 'outofstock',
				attributes: [
					{
						name: 'Colour',
						option: 'Red',
					},
				],
			}
		);
		const variation = await this.api.post(
			`${ WC_API_PATH }/products/${ this.productData.id }/variations`,
			{
				regular_price: '1.00',
				stock_status: 'outofstock',
				attributes: [
					{
						name: 'Colour',
						option: 'White',
					},
				],
			}
		);
		this.outOfStockVariationType = 'defined';
		this.variationId = variation.data.id;
	}

	async iCompleteTheSignUpProcess() {
		await this.page.getByRole( 'button', { name: 'Notify me' } ).click();
	}

	async iSeeANoticeWithFurtherInstructions() {
		await expect(
			this.page.getByText( 'To join the waitlist, please' )
		).toBeVisible();
	}

	async iFollowTheSignUpPromptLink() {
		await this.page
			.locator( 'li' )
			.filter( {
				hasText: this.productData.name,
			} )
			.getByRole( 'link', { name: 'Join the waitlist' } )
			.click();
	}

	async iAmOnTheCatalogPage() {
		await this.page.goto( '/shop/' );
	}

	async signUpPromptsInCatalogAreEnabled() {
		await setOption(
			request,
			this.baseURL,
			'wc_bis_loop_signup_prompt_status',
			'yes'
		);
	}

	async iSeeAPromptToSignUpToBeNotifiedWhenTheProductIsBackInStock() {
		await expect(
			await this.page
				.locator(
					`:has-text("${ this.productData.name }") ~ div:has-text("Out of stock. Join the waitlist to be notified when this product becomes available.")`
				)
				.nth( 0 )
		).toBeVisible();
	}

	async iSeeSomeActivityRelatedWithNotificationsISignedUpToReceiveInThePast() {
		await expect(
			await this.page
				.locator( `.wc-bis-notifications-activity-table tbody tr` )
				.count()
		).toBeGreaterThan( 0 );
	}

	async iSeeThatThePendingNotificationWasCancelled() {
		await expect(
			this.page.getByText( 'Pending notification cancelled.' )
		).toBeVisible();
	}

	async iSeeThatICanCancelPendingNotifications() {
		await expect(
			this.page.locator( `h2:has-text("Pending") + table tr a`, {
				hasText: 'Cancel',
			} )
		).toBeVisible();
	}

	async iCancelAPendingNotification() {
		this.page
			.locator( `h2:has-text("Pending") + table tr a`, {
				hasText: 'Cancel',
			} )
			.click();
	}

	async iSeeAPendingNotificationForTheProductIHaveSubscribedTo() {
		let expectedText;
		if ( this.outOfStockVariationType === 'defined' ) {
			expectedText = `${ this.productData.name } [-–] White`;
		} else {
			expectedText = this.productData.name;
		}
		await expect(
			this.page.locator( `h2:has-text("Pending") + table tr a`, {
				hasText: new RegExp( expectedText ),
			} )
		).toBeVisible();
	}

	async iSeeAPendingNotificationsTable() {
		await expect(
			await this.page
				.locator( 'h2:has-text("Pending") + table tr' )
				.count()
		).toBeGreaterThan( 0 );
	}

	async iReSendAVerificationEmail() {
		await this.page
			.locator( `h2:has-text("Pending") + table tr a`, {
				hasText: 'Resend verification',
			} )
			.click();
	}

	async iSeeThatTheEmailWasResentSuccessfully() {
		await expect(
			this.page.getByText( 'Verification e-mail sent' )
		).toBeVisible();
	}

	async iSeeThatICanReSendVerificationEmails() {
		await expect(
			this.page.locator( `h2:has-text("Pending") + table tr a`, {
				hasText: 'Resend verification',
			} )
		).toBeVisible();
	}

	async iSeeThatTheNotificationWasCancelled() {
		await expect(
			this.page.getByText( 'Notification deactivated.' )
		).toBeVisible();
	}

	iCancelAnActiveNotification() {
		this.page
			.locator( `h2:has-text("Active") + table tr a`, {
				hasText: 'Deactivate',
			} )
			.click();
	}

	async iSeeThatICanCancelActiveNotifications() {
		await expect(
			this.page.locator( `h2:has-text("Active") + table tr a`, {
				hasText: 'Deactivate',
			} )
		).toBeVisible();
	}

	async iSeeANotificationForTheProductIHaveSubscribedTo() {
		let expectedText;
		if ( this.outOfStockVariationType === 'defined' ) {
			expectedText = `${ this.productData.name } [-–] White`;
		} else {
			expectedText = this.productData.name;
		}
		await expect(
			this.page.locator( `h2:has-text("Active") + table tr a`, {
				hasText: new RegExp( expectedText ),
			} )
		).toBeVisible();
	}

	async iSeeAnActiveNotificationsTable() {
		await expect(
			await this.page
				.locator( 'h2:has-text("Active") + table tr' )
				.count()
		).toBeGreaterThan( 0 );
	}

	async iAmOnTheStockNotificationsAccountPage() {
		await this.page.goto( '/my-account/backinstock/' );
	}

	async iSeeThatTheVariationIHadSignedUpForIsPreSelected() {
		await expect( this.page.getByLabel( 'Colour' ) ).toHaveValue( 'White' );
	}

	async iAmTakenToTheProductPageToCompleteMyPurchase() {
		await expect(
			this.page.getByRole( 'heading', {
				name: this.productData.name,
				exact: true,
			} )
		).toBeVisible();
	}

	async iClickTheButtonPromptingMeToPurchaseTheProduct() {
		await this.page.getByRole( 'link', { name: 'Shop Now' } ).click();
	}

	async iViewTheNotificationIReceivedViaEmail() {
		await this.page.goto(
			'/notification-email/?notification_id=' + this.notificationId
		);
	}

	async iCanSeeAConfirmationEmailWithDetailsAboutTheVariationProductISubscribedTo() {
		await this.iViewTheConfirmationIReceivedViaEmail();
		await this.iSeeSomeDetailsAboutTheVariationProductISubscribedTo();
	}

	async iCanSeeAConfirmationEmailWithDetailsAboutTheProductISubscribedTo() {
		await this.iViewTheConfirmationIReceivedViaEmail();
		await this.iSeeSomeDetailsAboutTheProductISubscribedTo();
	}

	async iSeeAMessageThatMyConfirmRequestWasSuccessful() {
		await expect(
			this.page.getByText(
				'Successfully verified stock notifications for'
			)
		).toBeVisible();
	}

	async iFollowTheConfirmLink() {
		await this.page
			.getByRole( 'link', { name: 'Confirm', exact: true } )
			.click();
	}

	async iSeeTheTitleOfTheVerificationEmail() {
		await expect(
			this.page.getByText(
				new RegExp( `Join the [“"]${ productName }[”"] waitlist.` )
			)
		).toBeVisible();
	}

	async iAmPromptedToVerifyMyRequest() {
		await expect(
			this.page.getByText(
				'Please follow the link below to complete the sign-up process'
			)
		).toBeVisible();
	}

	async iSeeALinkToVerifyMyRequest() {
		await expect(
			this.page.getByRole( 'link', { name: 'Confirm', exact: true } )
		).toBeVisible();
	}

	async iViewTheDoubleOptInVerificationIReceivedViaEmail() {
		await this.page.goto(
			'/verification-email/?notification_id=' + this.notificationId
		);
	}

	async iFollowTheLink() {
		await this.page.click( 'text=Click here' );
	}

	async iSeeThatMyRequestWasCancelled() {
		await expect(
			this.page.getByText( 'Successfully unsubscribe' )
		).toBeVisible();
	}

	async iSeeALinkToCancelMyRequest() {
		await expect(
			this.page.getByText(
				'You have received this message because your e-mail address was used to sign up for stock notifications on our store. Changed your mind? Click here to unsubscribe.'
			)
		).toBeVisible();
	}

	async iViewTheConfirmationIReceivedViaEmail() {
		await this.page.goto(
			'/confirmation-email/?notification_id=' + this.notificationId
		);
	}

	async iSeeSomeDetailsAboutTheProductISubscribedTo( name ) {
		await expect(
			this.page.getByText(
				new RegExp(
					`You have joined the [“"]${
						name || productName
					}[”"] waitlist.`
				)
			)
		).toBeVisible();
	}

	async iSeeThatSomeCustomersHaveAlreadySignedUp() {
		await expect(
			this.page.getByText( 'joined the waitlist' )
		).toBeVisible();
	}

	async numberOfCustomerWhoHaveJoinedTheWaitlistIsVisible() {
		await setOption(
			request,
			this.baseURL,
			'wc_bis_show_product_registrations_count',
			'yes'
		);
	}

	async signUpsAreDoubleOptInAndANewAccountIsCreatedOnSignUp() {
		this.scenario = 'double';
		await setOption(
			request,
			this.baseURL,
			'wc_bis_double_opt_in_required',
			'yes'
		);
		await setOption(
			request,
			this.baseURL,
			'wc_bis_create_new_account_on_registration',
			'yes'
		);
	}

	async signUpsAreSingleOptInAndANewAccountIsCreatedOnSignUp() {
		await setOption(
			request,
			this.baseURL,
			'wc_bis_opt_in_required',
			'yes'
		);
		await setOption(
			request,
			this.baseURL,
			'wc_bis_create_new_account_on_registration',
			'yes'
		);
		await this.api
			.post( `${ WC_API_PATH }/products`, {
				name: productName,
				type: 'simple',
				regular_price: productPrice,
				stock_status: 'outofstock',
			} )
			.then( ( response ) => {
				this.productData = response.data;
			} );
	}

	async iAmPromptedToCheckMyEmail() {
		await expect(
			this.page.getByText(
				'Thanks for signing up! Please complete the sign-up process by following the verification link sent to your e-mail.'
			)
		).toBeVisible();
	}

	async signUpsAreDoubleOptIn() {
		await setOption(
			request,
			this.baseURL,
			'wc_bis_double_opt_in_required',
			'yes'
		);
		await this.api
			.post( `${ WC_API_PATH }/products`, {
				name: productName,
				type: 'simple',
				regular_price: productPrice,
				stock_status: 'outofstock',
			} )
			.then( ( response ) => {
				this.productData = response.data;
			} );
		await setOption(
			request,
			this.baseURL,
			'wc_bis_opt_in_required',
			'yes'
		);
	}

	async signUpsAreSingleOptInWithCheckbox() {
		await this.api
			.post( `${ WC_API_PATH }/products`, {
				name: productName,
				type: 'simple',
				regular_price: productPrice,
				stock_status: 'outofstock',
			} )
			.then( ( response ) => {
				this.productData = response.data;
			} );
		await setOption(
			request,
			this.baseURL,
			'wc_bis_opt_in_required',
			'yes'
		);
	}

	async signUpsAreSingleOptInWithoutCheckbox() {
		await this.api
			.post( `${ WC_API_PATH }/products`, {
				name: productName,
				type: 'simple',
				regular_price: productPrice,
				stock_status: 'outofstock',
			} )
			.then( ( response ) => {
				this.productData = response.data;
			} );
		await setOption(
			request,
			this.baseURL,
			'wc_bis_account_required',
			'no'
		);
		await setOption(
			request,
			this.baseURL,
			'wc_bis_opt_in_required',
			'no'
		);
	}

	async iLogInToMyAccount() {
		await logIn( this.page, customer.email, customer.password, false );
	}

	async iSeeAPromptToLogInToMyAccount() {
		await expect(
			this.page.getByText(
				'Please log in to complete the sign-up process.'
			)
		).toBeVisible();
	}

	async signUpsAreLimitedToLoggedInUsers() {
		await this.api
			.post( `${ WC_API_PATH }/products`, {
				name: productName,
				type: 'simple',
				regular_price: productPrice,
				stock_status: 'outofstock',
			} )
			.then( ( response ) => {
				this.productData = response.data;
			} );
		await setOption(
			request,
			this.baseURL,
			'wc_bis_account_required',
			'yes'
		);
	}

	async aSimpleProductThatIsOutOfStock() {
		await this.api
			.post( `${ WC_API_PATH }/products`, {
				name: productName,
				type: 'simple',
				regular_price: productPrice,
				stock_status: 'outofstock',
			} )
			.then( ( response ) => {
				this.productData = response.data;
			} );
	}

	async theProductHasNotifications() {
		return this.api
			.post( `${ WC_API_PATH }/create-bis-notifications`, {
				product_id: this.productData.id,
			} )
			.then( ( response ) => {
				this.notificationId = response.data.data;
			} );
	}

	async theVariationHasNotifications() {
		return this.api
			.post( `${ WC_API_PATH }/create-bis-notifications`, {
				product_id: this.variationId,
			} )
			.then( ( response ) => {
				this.notificationId = response.data.data;
			} );
	}

	async iAmViewingThePageOfASimpleProductThatIsOutOfStock() {
		await this.aSimpleProductThatIsOutOfStock();
		this.page.goto( this.productData.permalink );
	}

	async aVariableProductThatContainsOutOfStockVariationsWithAnAttributeWithValueAny() {
		await this.api
			.post( `${ WC_API_PATH }/products`, {
				name: `A Variable Product with any ${ now }`,
				type: 'variable',
				attributes: [
					{
						name: 'Colour',
						visible: true,
						variation: true,
						options: [ 'Red', 'Green', 'Blue', 'White' ],
					},
				],
			} )
			.then( ( response ) => {
				this.productData = response.data;
			} );
		const variation = await this.api.post(
			`${ WC_API_PATH }/products/${ this.productData.id }/variations`,
			{
				regular_price: '1.00',
				stock_status: 'outofstock',
			}
		);
		this.outOfStockVariationType = 'free';
		this.variationId = variation.data.id;
	}

	async iAmViewingThePageOfAVariableProductThatContainsOutOfStockVariationsWithAnAttributeWithValueAny() {
		await this.aVariableProductThatContainsOutOfStockVariationsWithAnAttributeWithValueAny();
		await this.page.goto( this.productData.permalink );
	}

	iChooseAVariationThatIsInStock() {
		return this.page.getByLabel( 'Colour' ).selectOption( 'Red' );
	}

	iChooseAVariationThatIsOutOfStock() {
		return this.page.getByLabel( 'Colour' ).selectOption( 'White' );
	}

	async iSeeSomeDetailsAboutTheVariationProductISubscribedTo() {
		// this is part of a regex and contains two types of dash characters.
		await this.iSeeSomeDetailsAboutTheProductISubscribedTo(
			this.outOfStockVariationType === 'defined'
				? `A Variable Product ${ now } [-–] White`
				: `A Variable Product with any ${ now }`
		);
	}

	async aVariableProductThatContainsOutOfStockVariations() {
		await this.api
			.post( `${ WC_API_PATH }/products`, {
				name: `A Variable Product ${ now }`,
				type: 'variable',
				attributes: [
					{
						name: 'Colour',
						visible: true,
						variation: true,
						options: [ 'Red', 'Green', 'Blue', 'White' ],
					},
				],
			} )
			.then( ( response ) => {
				this.productData = response.data;
			} );
		await this.api.post(
			`${ WC_API_PATH }/products/${ this.productData.id }/variations`,
			{
				regular_price: '1.00',
				attributes: [
					{
						name: 'Colour',
						option: 'Red',
					},
				],
			}
		);
		const variation = await this.api.post(
			`${ WC_API_PATH }/products/${ this.productData.id }/variations`,
			{
				regular_price: '1.00',
				stock_status: 'outofstock',
				attributes: [
					{
						name: 'Colour',
						option: 'White',
					},
				],
			}
		);
		this.outOfStockVariationType = 'defined';
		this.variationId = variation.data.id;
	}

	async iAmViewingThePageOfAVariableProductThatContainsOutOfStockVariations() {
		await this.aVariableProductThatContainsOutOfStockVariations();
		await this.page.goto( this.productData.permalink );
	}

	async iSeeThatIAlreadyJoinedTheWaitlist() {
		return expect(
			this.page.getByText(
				'You have already joined the waitlist! Click here to manage your notifications.'
			)
		).toBeVisible();
	}

	iGoToTheProductPage() {
		this.page.goto( this.productData.permalink );
	}

	iSeeAPromptToManageMyNotifications() {
		return expect(
			this.page.getByText( 'Manage notifications' )
		).toBeVisible();
	}

	iDontSeeASignUpButton() {
		return expect( this.page.getByText( 'Notify me' ) ).not.toBeVisible();
	}

	iSeeASignUpButton() {
		return expect( this.page.getByText( 'Notify me' ) ).toBeVisible();
	}

	iClickTheNotifyMeButton() {
		return this.page.click( 'text=Notify me' );
	}

	iSeeThatMySignupRequestWasSuccessful() {
		return expect(
			this.page.getByText(
				this.scenario === 'double'
					? 'Thanks for signing up!'
					: 'You have successfully signed up'
			)
		).toBeVisible();
	}

	iDontSeeAPromptToSignUpAndBeNotifiedWhenTheProductIsBackInStock() {
		return expect(
			this.page.getByText(
				'Want to be notified when this product is back in stock?'
			)
		).not.toBeVisible();
	}

	async iTickTheCheckbox() {
		await this.page
			.getByLabel(
				'Use this e-mail address to send me availability alerts and updates.'
			)
			.check();
	}

	iSeeAPromptToSignUpAndBeNotifiedWhenTheProductIsBackInStock() {
		return expect(
			this.page.getByText(
				'Want to be notified when this product is back in stock?'
			)
		).toBeVisible();
	}

	iSeeAFieldToEnterMyEmail() {
		return expect(
			this.page.getByPlaceholder( 'Enter your e-mail' )
		).toBeVisible();
	}

	iDontSeeAFieldToEnterMyEmail() {
		return expect(
			this.page.getByPlaceholder( 'Enter your e-mail' )
		).not.toBeVisible();
	}

	iSeeAnOptInCheckbox() {
		return expect(
			this.page.getByLabel(
				'Use this e-mail address to send me availability alerts and updates.'
			)
		).toBeVisible();
	}

	iDontSeeAnOptInCheckbox() {
		return expect(
			this.page.getByLabel(
				'Use this e-mail address to send me availability alerts and updates.'
			)
		).not.toBeVisible();
	}

	iEnterMyEmail() {
		return this.page
			.getByPlaceholder( 'Enter your e-mail' )
			.fill( customer.email );
	}

	async deleteCurrentProduct() {
		await this.api.post( `${ WC_API_PATH }/products/batch`, {
			delete: [ this.productData.id ],
		} );
	}
}

export default AcceptanceHelper;
