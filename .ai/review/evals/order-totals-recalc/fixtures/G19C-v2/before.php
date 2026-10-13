<?php
final class WC_Order_Total_Controller {
    public function persist( WC_Order $order ) {
        $order->save();
    }
}
