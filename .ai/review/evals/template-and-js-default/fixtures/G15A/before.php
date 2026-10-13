<?php
/* Scenario context: Product 42 is a purchasable simple product. Server validates POST and processes add-to-cart. Page builders can render this template without any Woo frontend scripts; enabled native form submission is the released fallback. */
/** Product purchase form. @version 1.0.0 */
?>
<form method="post">
    <input type="hidden" name="add-to-cart" value="42">
    <button type="submit" class="single_add_to_cart_button">Add to cart</button>
</form>
<?php
