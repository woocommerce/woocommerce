---
post_title: How to enable High Performance Order Storage
menu_title: Enable HPOS
tags: how-to
---

From WooCommerce 8.2, released on October 2023, HPOS is enabled by default for new installations. Existing stores can switch to the "High-Performance Order Storage" from "WordPress Posts Storage" by following the below steps.

To activate High-Performance Order Storage, existing stores will firs   t need to get both the posts and orders table in sync, which can be done by turning on the setting "**Enable compatibility mode (synchronizes orders to the posts table)**".

1. Navigate to **WooCommerce > Settings > Advanced > Features**
2. Turn on the **"Enable compatibility mode (synchronizes orders to the posts table)"** setting.

    ![Enable HPOS Screen](https://developer.woocommerce.com/wp-content/uploads/2023/12/New-Project-4.jpg)

3. Once this setting is activated, background actions will be scheduled.

    - The action `wc_schedule_pending_batch_process` checks whether there are orders that need to be backfilled.
    - If there are, it schedules another action `wc_run_batch_process` that actually backfills the orders to post storage.
    - You can either wait for these actions to run on their own, which should be quite soon, or you can go to **WooCommerce > Status > Scheduled Actions**, find the actions and click on the run button.
    - The action will backfill 25 orders at a time, if there are more orders to be synced, then more actions will be scheduled as soon as the previous actions are completed.

    ![wc_schedule_pending_batch_process Screen](https://developer.woocommerce.com/wp-content/uploads/2023/12/2.jpg)
    ![wc_run_batch_process Screen](https://developer.woocommerce.com/wp-content/uploads/2023/12/New-Project-5.jpg)

4. After both tables are successfully synchronized, you'll be able to select the option to switch to High-Performance Order Storage (HPOS).
  
    - It is advisable to maintain compatibility mode for some time to ensure a seamless transition. In case of any issues, reverting to the post table can be done instantly.

