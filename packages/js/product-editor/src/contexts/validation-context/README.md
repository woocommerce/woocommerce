# Validations and error handling

This directory contains some components and hooks used for validations in the product editor.

## What happens when there is an error in the form?

1. Fields registered in the validator context [will get validated](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/contexts/validation-context/validation-provider.tsx#L87-L110). A field can be registered by making use of the useValidation hook.
    - For instance:

    ```javascript
    const {
      ref: myRef,
      error: myValidationError,
      validate: validateMyField,
    } = useValidation <
    Product >
    ( 'myfield',
    async function myFieldValidator() {
      if ( ! myField ) {
        return {
          message: 'My error message',
          context: clientId,
        };
      }
    },
    [ myField ] );
    ```

2. If a field has an error, it returns an object consisting of the error/validation message, the context, and the validatorId ([link](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/contexts/validation-context/validation-provider.tsx#L74) ).
    - The `context` contains the block Id, and the `validatorId` a unique ID for the validator specifically ( generally a prefix with the block id ).
    - If, for instance, the name field is empty, the validation will fail and will throw an object like this:

    ```javascript
    { message: 'Product name is required.'; context: [block id]; validatorId: [prefix + block id] }
    ```

    - This is the result of the name validator and the [validatorId addition](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/contexts/validation-context/validation-provider.tsx#L69).

    ```javascript
    async function nameValidator() {
      if ( ! name || name === AUTO_DRAFT_NAME ) {
        return {
          message: __( 'Product name is required.', 'woocommerce' ),
          context: clientId,
        };
      }

      if ( name.length > 120 ) {
        return {
          message: __(
            'Please enter a product name shorter than 120 characters.',
            'woocommerce'
          ),
          context: clientId,
        };
      }
    },
    ```

3. If an error is present on the form we will show an error snackbar with the error message. We will actually include a **View error** link if the field with the relevant error is not visible ( like on another tab ). Clicking the **View error** link will direct users to the relevant field.
    - We create this link by making use of the `context` property (the block id), this makes use of the `useBlocksHelper()` hook to get the parent tab id. We can do this by making use of the `core/block-editor` store and using `getBlockParentsByBlockName` (link to relevant code).
    - When the field with [the error is not visible](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/hooks/use-error-handler.ts#L105), a link pointing to it will be added to the snackbar.
    - Otherwise, the error will be dismissed automatically.
    - The hook `useErrorHandler` is used to get the [error props](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/hooks/use-error-handler.ts#L79).
    - The error shown will depend [on the error code](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/hooks/use-error-handler.ts#L92).
    - [As you can see here](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/hooks/use-error-handler.ts#L157-L162), if the error doesn't have a code, the default message will be `Failed to save product.`
    - The context is used to [get the parent tab](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/hooks/use-blocks-helper/use-blocks-helper.ts#L7) id and the validatorId to [focus on the field](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/hooks/use-error-handler.ts#L68).

Finally, [the snackbar with the error](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/product-editor/src/components/header/publish-button/publish-button.tsx#L70) message and props will be displayed.

![Product editor error snackbar](https://developer.woocommerce.com/wp-content/uploads/sites/2/2024/07/product-editor-error-snack-bar-e1721670028482.png)

## Limitations

The server errors, such as `duplicated SKU`, are not being mapped yet.
