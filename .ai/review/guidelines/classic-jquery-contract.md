# Classic jQuery request and event contract

**Source:**

- #68365 sent classic checkout data as a pre-serialized string (`encodeApostrophes($.param(data))`); third-party `ajaxPrefilter`s reading `originalOptions.data` as an object broke. Reverted in #69644 / #69649 (11.2.1).
- #68556 (same change on cart) reverted in #69696.
- #61506: reverted a global anchor handler.
- #51449 (9.3.2): `added_to_cart` triggered without a button.
- #62529 (10.4.3): undo after removal.
- #56047 (9.7.1).

**What broke:** Extensions hook classic cart and checkout through jQuery ajax options and `document.body` events. Changing the data shape or event args broke them, including coupon-blocking logic.

**Pattern to catch:** In `client/legacy/js`, changes to `$.ajax` data shape (object vs string), trigger names, args, or targets (`document.body`), or global delegated handlers.

**How to confirm:** List the events and params extensions bind to (`update_checkout`, `updated_wc_div`, `added_to_cart`). Ask whether `data` is still an object when prefilters see it.

**Don't flag when:** Internal helpers not reachable through events or ajax options.
