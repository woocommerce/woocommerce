# RSM-438 — Testing instructions

Manual QA for the verify + confirmation email wiring. Sprint scratch — delete before GA.

## Setup

1. Enable the alpha: `define( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );` in `wp-config.php`.
2. **WooCommerce → Settings → Products → Customer stock notifications**
   - Allow signups: **On**
   - Require double opt-in: **On** (for T1–T5) / **Off** (for T6)
3. Mailpit (or equivalent) connected so you can inspect outgoing email.
4. Create / select one out-of-stock simple product and one out-of-stock variable product.

## T1 — Verify email fires on signup (double opt-in on)

1. As a logged-out guest, sign up for notifications on the out-of-stock simple product.
2. Expected notice: _"Thanks for signing up! Please complete the sign-up process by following the verification link sent to your e-mail."_
3. Expected Mailpit: one **"Join the &quot;{product_name}&quot; waitlist"** email to the guest's address, with a confirmation button.
4. Confirm the button URL carries `utm_source=back-in-stock-notifications&utm_medium=email&email_link_action=verify&...`.

## T2 — Verified/confirmation email fires on successful verification

1. Click the Confirm button in the T1 email.
2. Browser redirects to the shop page with the success notice _"Successfully verified stock notifications for &quot;{product}&quot;"_.
3. Expected Mailpit: one **"You're on the waitlist …"** (or whatever the subject is) email containing the unsubscribe link.
4. Unsubscribe URL should carry `utm_source=back-in-stock-notifications&utm_medium=email&email_link_action=unsubscribe&...`.

## T3 — Frontend resend link re-sends verify email

1. Re-submit the signup form (same product + same guest email) before clicking the confirm link.
2. Expected notice: _"You have already joined this waitlist. Please complete the sign-up process by following the verification link sent to your e-mail."_ + a **Resend verification** action link.
3. Click the **Resend verification** link.
4. Expected Mailpit: a **new** verify email to the guest. Link in the old verify email should still decode to the same notification id but the key is regenerated (old link stops verifying).
5. Success notice on the redirect: _"Verification email sent to …"_.

## T4 — Frontend resend is rate-limited to 60s

1. Immediately after T3, click **Resend verification** a second time.
2. Expected: no new email sent. Notice shown: _"Please wait before requesting another verification email."_
3. Wait 60 seconds and retry — new email arrives.

## T5 — Admin "Resend verification email" button (Must-fix #9 regression)

1. In **WooCommerce → Notifications**, open any notification whose status is **Active** (i.e., already verified).
2. Select **Resend verification email** from the actions dropdown and save.
3. Expected: an **error** notice ("Cannot resend verification: this notification is already verified or cancelled."), no email sent.
4. Find a **Pending** notification (sign up a fresh guest but don't confirm) and repeat.
5. Expected: a new verify email arrives in Mailpit, success notice shows _"Verification email sent to …"_.

## T6 — Double opt-in disabled: verify email suppressed

1. Toggle **Require double opt-in: Off**.
2. Sign up for notifications as a new guest on any out-of-stock product.
3. Expected: success notice about being on the waitlist (no "check your email"). Notification row goes straight to **Active**.
4. Expected Mailpit: **no** verify email. No verified email either (we only confirm-on-verify; immediate-active doesn't fire the welcome email).

## T7 — UTM params present on back-in-stock email

1. With an **Active** notification in place, flip the target product back to **In stock** and run the processor (either wait for the cron or trigger the Action Scheduler entry `wc_send_stock_notifications_batch`).
2. Expected Mailpit: the **back-in-stock** email.
3. Confirm the CTA button URL and unsubscribe link both carry `utm_source=back-in-stock-notifications&utm_medium=email`.

## Regression check — PR #64329 flows still work

1. Admin **Cancel** action still writes `cancellation_source=ADMIN` and `date_cancelled`, leaves `date_notified` `NULL`.
2. Unsubscribe link from any email still cancels with `cancellation_source=USER`.
3. Verification link still accepts a valid key and rejects a tampered one.
