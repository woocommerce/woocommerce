<?php
/* Scenario context: Optional enhancement reveals purchase-progress during async submit when loaded. Form remains a native POST fallback; progress text is new and not a purchase control. */
/** Product purchase form. @version 1.0.0 */
?>
<form method="post">
    <input type="hidden" name="add-to-cart" value="42">
    <button type="submit" class="single_add_to_cart_button">Add to cart</button>
</form>
<?php
