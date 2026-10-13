<?php
/* Scenario context: Product 42 is purchasable; server accepts this form POST. A new frontend enhancement removes hidden when loaded, but page builders and no-JS clients can render without that enhancement. */
/** Product purchase form. @version 1.1.0 */
?>
<form method="post" hidden>
    <input type="hidden" name="add-to-cart" value="42">
    <button type="submit" class="single_add_to_cart_button">Add to cart</button>
</form>
<?php
