---
post_title: Managing the lifecycle of WooCommerce block templates
menu_title: Block template lifecycle
tags: reference
---

A block template is a tree structure of blocks that define the product editor's form structure.

A template can be modified by using the block template API to add new blocks (groups, sections, and fields) as well as remove existing ones.

A template is implemented in PHP and sent to the client. The client then renders the template using React.

The lifecycle of a template is as follows:

- [Registration](#registration)
- [Creation](#creation)
- [Block addition and removal](#block-addition-and-removal)
    - [Actions](#actions)
- [Sent to client](#sent-to-client)
- [Rendered on client](#rendered-on-client)

## Registration

A template class can be registered with the `Automattic\\WooCommerce\\LayoutTemplates\\LayoutTemplateRegistry`. All template classes must implement the `Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface` interface.

Registration is required in order for the template to be available to be sent to the client (via the `/wc/v3/layout-templates` REST API endpoint).

The default templates are registered in the `rest_api_init` action hook, priority 10.

Blocks can be added or removed from a template before or after it is registered, but the template cannot be modified after it is sent to the client.

**In order for the template to be sent to the client, it should be registered in or before the `rest_request_before_callbacks` filter hook. They can be registered in the `rest_api_init` hook.**

## Creation

Templates are instantiated by the handler for the `/wcv3/layout-templates` REST API endpoint.

## Block addition and removal

After a template instance is created, blocks can be added to or removed from a template using the `add_block()` and `remove_block()` methods, or similar methods that are specific to the type of block being added or removed, such as `add_section()` and `remove_section()`.

See the [Automattic\WooCommerce\Admin\BlockTemplates](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Admin/BlockTemplates/README.md) documentation for more information about these methods.

You can modify template instances in a hook for the following action:

- `woocommerce_layout_template_after_instantiation`

### Actions

The following actions are fired when blocks are added to or removed from a template, to support extensibility:

-  `woocommerce_product_editor_block_template_{template_name}_after_add_block_{block_id}`
-  `woocommerce_product_editor_block_template_after_add_block`
-  `woocommerce_product_editor_block_template_{template_name}_after_remove_block_{block_id}`
-  `woocommerce_product_editor_block_template_after_remove_block`

**In order for your action hooks to be called for all block additions and removals for a template, you should call `add_action()` for each of these hooks before the template is instantiated, in or before an `rest_api_init` action hook, priority 9 or lower. If your hooks are not being called, verify that you are hooking them up in an action that is called when REST API endpoints are called.**

See the [Automattic\WooCommerce\Admin\BlockTemplates](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Admin/BlockTemplates/README.md) documentation for more information about these hooks.

## Sent to client

A template is sent to the client in the handler for the `/wcv3/layout-templates` REST API endpoint, after the `rest_request_before_callbacks` filter hook.

Any template modification after this point will not be sent to the client.

## Rendered on client

When the template is rendered on the client, all blocks in the template have their `hideConditions` and `disableConditions` evaluated to determine whether they should be rendered or not.
