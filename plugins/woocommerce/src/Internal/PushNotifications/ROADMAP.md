# WooCommerce-driven Push Notification: Roadmap

This document is intended to describe the purpose and planned changes for the WooCommerce: Self-driven Push Notifications project, to assist folks in reviewing related PRs and understanding the wider context of the changes.

The following terms have been used in this post to mean:

- **"WPCOM"**: WordPress.com hosted infrastructure, including the public API, databases, and asynchronous jobs system
- **"Remote WooCommerce Site"**: the blog running WordPress and WooCommerce, which could be self-hosted, a WordPress.com hosted simple site, a WP Cloud hosted site, etc

## Contents

- Goals
- Key Flows
    - Before
    - After
- Data models
    - Push Token
    - Push Notification Preferences
- API Surface
- Steps
    - Add foundations to support the push notification functionality
    - Add token registration/unregistration endpoint
    - Add asynchronous internal send endpoint
    - Add send functionality
    - Add retry functionality
    - Add new booking notification
    - Update notification setting controls
- Notes
    - Failures/retries
    - Users who don't connect Jetpack
    - Users who don't upgrade WooCommerce
    - WordPress.com Notification Control Center preferences

## Goals

We are adding the ability for WooCommerce to trigger its own Android and iOS push notifications. The reasons are discussed in more detail in this post, but briefly, the goals of this will be:

- The Jetpack Sync plugin will no longer be required to enable push notifications for sites
- Reduced complexity and easier debugging of the push notifications send process (which currently requires tracking orders/reviews through multiple systems)
- Ability to send notifications to site users who do not have a connected WordPress.com account (e.g. shop managers)
- Improved ease of adding new notifications (which currently requires changes in the apps, WPCOM, and Jetpack Sync)
- WooCommerce notifications not creating noise in the WordPress.com masterbar

The new functionality will be available for sites that:

- Have the Jetpack connection plugin installed (which now ships with WooCommerce Core)
- Have an active Jetpack connection
- Have the wc_push_notifications feature enabled

## Key flows

### Before

This section summarises the way WooCommerce push notifications currently work.

**Push token registration:**

1. User logs into site on app
2. App retrieves a push token from the device (which is a string we can use to send notifications to the device)
3. App sends token to WPCOM with information about the owner, app, and device
4. WPCOM stores the token, deduplicating it to ensure only one token with this value or device UUID exists for the user

**Push notification trigger/send:**

1. Order is placed on remote WooCommerce site (or review is created)
2. Remote WooCommerce site triggers a Jetpack Sync request
3. Order is sync'ed from the remote WooCommerce site to WPCOM
4. If WPCOM detects that a notification has not already been sent for the order (via meta), then a WPCOM note is created and a push notification is triggered for this note
5. The push notifications infra generates a payload from the note, and sends it via FCM (Android, browsers) or APNS (Apple) to registered tokens belonging to any admins or shop managers of that site

**Push notification retries:**

1. If the request to FCM/APNS fails/gets an error response, a WPCOM job is queued to try again
2. Retry delay is read from the response header (e.g. if rate limited) or 10 seconds
3. Notification is retried a max of 4 times (so 5 total tries)

### After

This section summarises the way WooCommerce push notifications will work when this project is complete.

**Push token registration:**

1. User logs into site on app
2. App retrieves a push token from the device
3. App tries to register token with remote WooCommerce site - if it fails (e.g. because Woo version not supported, or Jetpack not connected), it registers the token with WPCOM instead
4. Whichever location it end up stored in, deduplication still occurs

**Push notification trigger/send:**

1. Order is placed on remote WooCommerce site (or review is created)
2. Remote WooCommerce site recognises from hooks that a notification should be triggered
3. Internal async request is made in the checkout (or review) request shutdown to trigger the notifications
4. Internal async endpoint makes a request to a new WPCOM endpoint to send the notifications
5. WPCOM validates the data and returns errors if it can't attempt to send the notification

**Push notification retries:**

1. If the send request to WPCOM returns a failure, queue a retry using Action Scheduler
2. Retry a max of 4 times, with exponential backoff
3. If retries are not successful, store/log the failure

## Data models

### Push Token

- **Description**: represents a token string that can be used to send a push notification to a mobile device.
- **Storage**: custom post type (`wc_push_token`) - based on existing data in WordPress.com, we expect most users to have less than 10 push tokens each (usually 1-3)
- **Meta**:
    - **Token**: the token string that can be used to send a notification to a specific device
    - **Device UUID**: a unique identifier representing the device, randomly generated by the app
    - **Platform**: the platform the device belongs to, these match values used in the WPCOM push notifications infra: `apple`, `android`, or `browser`
    - **Origin**: an environment-specific string representing the app that sent the token, `com.woocommerce.android`, `com.woocommerce.android:dev`, `com.automattic.woocommerce`, or `com.automattic.woocommerce:dev`
- **Usage**:
    - Register/create push token endpoint: lookup by user ID, save new entry/update existing entry by token ID, retrieve meta by token ID, save meta
    - Unregister/delete push token endpoint: lookup by token ID, delete by token ID
    - Async send endpoint: lookup all, retrieve meta by token ID

### Push Notification Preferences

- **Description**: Represents preferences for the notifications and devices the user has enabled/disabled notifications for
- **Storage**: custom post type (`wc_push_notification_preferences`) - content is a JSON encoded list of enabled notifications or map of notifications to enabled status (true/false)
- **Usage**:
    - Update notification preferences endpoint: lookup by user ID, create entry, update entry by entry ID
    - Async send endpoint: lookup by user ID

## API surface

This library will be in the `src/Internal` directory and is not intended to be used by external users/developers.

### New API endpoints

- **Register token:**
    - Endpoint: `POST /wp-json/wc-push-notifications/push-tokens`
    - Auth: user-identifying token

- **Unregister token:**
    - Endpoint: `DELETE /wp-json/wc-push-notifications/push-tokens/{id}`
    - Auth: user-identifying token

- **Get notification preferences:**
    - Endpoint: `GET /wp-json/wc-push-notifications/preferences`
    - Auth: user-identifying token

- **Save notification preferences:**
    - Endpoint: `POST /wp-json/wc-push-notifications/preferences`
    - Auth: user-identifying token

- **Async send triggered notifications:**
    - Endpoint: `POST /wp-json/wc-push-notifications/send`
    - Auth: token generated by the async dispatcher, verified in the async endpoint

## Steps

1. **Add foundations to support the push notification functionality**
   - FeaturesController entry
   - Push token entity/DTO
   - Push token data store (CRUD + the ability to find a token which matches a token value or device ID)
   - Any required exception classes
   - Feature class that will load relevant files if the push notification functionality should be enabled (i.e. if Jetpack connected and feature is enabled)

2. **Add token registration/unregistration endpoint:**
   - This will be used to register and unregister push tokens generated by the device on the remote WooCommerce site
   - It will validate token and device UUID formats, and enum values for app platform and origin
   - If a token already exists for this user with either a matching device UUID or token value, it will update the existing version, not create a duplicate
   - It will authorize access for authenticated users with a valid role (admin, shop manager)

3. **Add asynchronous internal send endpoint:**
   - This will be used to send a collection of notifications to WPCOM
   - It will be used by requests that trigger notifications to actually send those notifications
   - It will authorize the request based on a temporarily stored token with an expiry (instead of a transient or nonce, in order to support multi-server/loadbalanced sites)
   - It will set appropriate timeouts to ensure the async process can't be stuck 'processing', and use database locks to ensure that notifications for the order/review can only be processed once.

4. ** Add get notification preferences endpoint:**
   - This will be used to get the notification preferences for the logged in user for each notification type and each of their devices
   - Available for admins and shop managers
   - Will allow access via WP Admin and via the app

5. **Add update notification preferences endpoint:**
   - This will be used to update the notification preferences for the logged in user for each notification type and each of their devices
   - Available for admins and shop managers
   - Will allow access via WP Admin and via the app

6. **Add send functionality:**
   - Will respond to `woocommerce_new_order` and `woocommerce_order_status_changed` to send a new order notification for orders with one of the following statuses: processing, on-hold, completed, pre-order, pre-ordered, partial-payment
   - Will respond to `comment_post` to send a review notification for comments of the type review
   - Triggered notifications will be 'remembered' during the request, and then sent asynchronously to an internal endpoint during the request shutdown process - this should avoid delaying/slowing the current request for the user. The process will generate and store a token with an expiry, that will be verified and consumed by the internal async endpoint
   - The internal async endpoint will send the notifications to a new WPCOM endpoint (authenticating using the Jetpack site token) which will do some validation before sending the notification through the WPCOM push notifications infra
   - If the request fails or returns an error, the notifications will be queued for retry via the Action Scheduler

7. **Add retry functionality:**
   - Retries will be attempted a max of 4 times, resulting in a total of 5 send attempts
   - Retry delay will be based on headers (e.g. in case of rate limiting) where present, or will use exponential backoff (60s, 5m, 15m, 60m)
   - The Action Scheduler will be used to queue retries
   - If all retries fail, it will log (and potentially store) the failures for debugging later

8. **Add new booking notification:**
   - Will be triggered when a new booking is made
   - Will be processed in the same way as other notifications

9. **Update notification setting controls:**
   - WooCommerce notifications for compatible sites will be hidden in the notification controls in WordPress.com
   - WooCommerce notifications controls will be added in WooCommerce Core settings
   - Will use the notifications preferences endpoint mentioned above

## Notes

### Failures/retries

- Due to WPCOM sending notifications asynchronously, a successful response from the WPCOM send endpoint does not mean the notification was successfully sent to APNS/FCM, only that WPCOM committed to making the request
- Failures to send to APNS/FCM will be handled by WPCOM's retry system, failures to send to WPCOM will be handled by WooCommerce's retry system.
- A successful response from FCM/APNS doesn't mean the notification was successfully delivered, only that FCM/APNS committed to trying to deliver it. We can't control/detect whether the notification was actually delivered from the server side, or the reason if it wasn't.

### Users who don't connect Jetpack

These users won't receive push notifications - this is the same as the existing behaviour, which also requires a Jetpack connection.

### Users who don't upgrade WooCommerce

- Notifications for these users will be processed in the existing way - via Jetpack sync
- Some users may have old and updated WooCommerce stores, meaning that they have tokens in both systems. We investigated avoiding duplicate notifications for these users here

### WordPress.com Notification Control Center preferences

- These will still be respected for all WooCommerce notifications, as it is part of our duplicate notification avoidance strategy
