# Action Scheduler and cron lifecycle

**Source:**

- #60607 (10.1.2): `woocommerce_cancel_unpaid_orders` queued with `unique` while self-rescheduling, so it stopped recurring after the #59325 cron-to-AS move.
- #60360 / #60417 (10.1.1): recurring callbacks whose classes only loaded in some requests failed.
- #64820: deferred-email object args broke after the move to AS in #63832.
- #61907, #57592, #68558: duplicate scheduled actions.
- #46630 (8.8.2), #52725 (9.4.1), #62419 (10.4.1), #68523, #56532: AS called before it was ready.
- #68527, #58532: hooks left behind on uninstall and deactivate.

**What broke:** Scheduling changes silently stopped recurring jobs, ran callbacks in requests where their class was not loaded, or left duplicate and orphaned actions. These shipped and needed point releases.

**Pattern to catch:** Any added or changed `as_schedule_*` / `as_enqueue_async_action` / WP-cron call or callback registration. Watch for:

- a self-rescheduling job using `unique = true` where the next action collides with the running action’s actual uniqueness key;
- a callback class not loaded in the AS runner request;
- payload values whose JSON round trip changes the type or shape expected by the callback, especially objects that become arrays; the required args array and JSON-compatible nested arrays are not intrinsically unsafe;
- no has-scheduled check, so duplicates pile up;
- `as_*` called before `action_scheduler_init`;
- hooks moved from WP-cron to AS but not cleared from cron;
- hooks missing from uninstall or deactivation cleanup.

**How to confirm:** Read the callback's `add_action` site and check which file loads it (see `register_recurring_actions` in `class-woocommerce.php`). Grep `uninstall.php` and `unschedule_*` for the hook name. Check for `function_exists( 'as_...' )` or the version-tolerant helper from #68523.

Verify the installed Action Scheduler version, API signature, and store’s actual pending/running uniqueness key before claiming jobs collide. The position of the unique parameter differs across scheduling APIs, and arguments can be part of the uniqueness key.

**Don't flag when:** `unique = true` on a one-shot action when jobs sharing its actual uniqueness key may safely be coalesced. A scalar ID argument that the callback re-fetches, or JSON-compatible data whose type and shape are preserved for the callback.
