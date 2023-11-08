# Block template lifecycle

A block template is a tree structure of blocks that define the product editor's form structure.

A template can be modified by using the block template API to add new blocks (groups, sections, and fields) as well as remove existing ones.

A template is implemented in PHP and sent to the client. The client then renders the template using React.

The lifecycle of a template is as follows:

- [Creation](#creation)
- [Block addition and removal](#block-addition-and-removal)
    - [Actions](#actions)
- [Registration](#registration)
- [Sent to client](#sent-to-client)
- [Rendered on client](#rendered-on-client)

## Creation

A template instance is created by instantiating a class that implements the `Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface` interface.

**A template should be instantiated in or after an `init` action hook, priority 4 or higher.**

## Block addition and removal

After a template instance is created, blocks can be added to or removed from a template using the `add_block()` and `remove_block()` methods, or similar methods that are specific to the type of block being added or removed, such as `add_section()` and `remove_section()`.

Blocks can be added or removed immediately after instantiation.

See the [Automattic\WooCommerce\Admin\BlockTemplates](../../plugins/woocommerce/src/Admin/BlockTemplates/README.md) documentation for more information about these methods.

### Actions

The following actions are fired when blocks are added to or removed from a template, to support extensibility:

-  `woocommerce_product_editor_block_template_{template_name}_after_add_block_{block_id}`
-  `woocommerce_product_editor_block_template_after_add_block`
-  `woocommerce_product_editor_block_template_{template_name}_after_remove_block_{block_id}`
-  `woocommerce_product_editor_block_template_after_remove_block`

**In order for your action hooks to be called for all block additions and removals for a template, you should call `add_action()` for each of these hooks before the template is instantiated, in or before an `init` action hook, priority 3 or lower.**

See the [Automattic\WooCommerce\Admin\BlockTemplates](../../plugins/woocommerce/src/Admin/BlockTemplates/README.md) documentation for more information about these hooks.

## Registration

After a template is instantiated, it can be registered with the `Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\BlockTemplateRegistry`.

Registration is required in order for the template to be sent to the client. 

Blocks can be added or removed from a template before or after it is registered, but the template cannot be modified after it is sent to the client.

**In order for the template to be sent to the client, it should be in or before the `admin_enqueue_scripts` action hook, priority 9 or lower.**

## Sent to client

A template is sent to the client in or after the `admin_enqueue_scripts` action hook, priority 10 or higher.

Any template modification after this point will not be sent to the client.

## Rendered on client

When the template is rendered on the client, all blocks in the template have their `hideConditions` evaluated to determine whether they should be rendered or not.
