<?php
/* Scenario context: Template uses static English copy in this isolated fixture. Product 42 is purchasable and POST server contract unchanged. Button text is presentation; selectors use the unchanged class. */
/** Product purchase form. @version 1.0.0 */
?>
<form method="post">
    <input type="hidden" name="add-to-cart" value="42">
    <button type="submit" class="single_add_to_cart_button">Add to cart</button>
</form>
<?php
