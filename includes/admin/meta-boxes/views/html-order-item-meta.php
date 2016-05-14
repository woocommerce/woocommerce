<div class="view">
    <?php
        global $wpdb;

        if ( $metadata = $order->has_meta( $item_id ) ) {
            echo '<table cellspacing="0" class="display_meta">';
            foreach ( $metadata as $meta ) {

                // Skip hidden core fields
                if ( in_array( $meta['meta_key'], apply_filters( 'woocommerce_hidden_order_itemmeta', array(
                    '_qty',
                    '_tax_class',
                    '_product_id',
                    '_variation_id',
                    '_line_subtotal',
                    '_line_subtotal_tax',
                    '_line_total',
                    '_line_tax',
                    'method_id',
                    'cost'
                ) ) ) ) {
                    continue;
                }

                // Skip serialised meta
                if ( is_serialized( $meta['meta_value'] ) ) {
                    continue;
                }

                // Get attribute data
                if ( taxonomy_exists( wc_sanitize_taxonomy_name( $meta['meta_key'] ) ) ) {
                    $term               = get_term_by( 'slug', $meta['meta_value'], wc_sanitize_taxonomy_name( $meta['meta_key'] ) );
                    $meta['meta_key']   = wc_attribute_label( wc_sanitize_taxonomy_name( $meta['meta_key'] ) );
                    $meta['meta_value'] = isset( $term->name ) ? $term->name : $meta['meta_value'];
                } else {
                    $meta['meta_key']   = wc_attribute_label( $meta['meta_key'], $_product );
                }

                echo '<tr><th>' . wp_kses_post( rawurldecode( $meta['meta_key'] ) ) . ':</th><td>' . wp_kses_post( wpautop( make_clickable( rawurldecode( $meta['meta_value'] ) ) ) ) . '</td></tr>';
            }
            echo '</table>';
        }
    ?>
</div>
<div class="edit" style="display: none;">
    <table class="meta" cellspacing="0">
        <tbody class="meta_items">
        <?php
            if ( $metadata = $order->has_meta( $item_id )) {
                foreach ( $metadata as $meta ) {

                    // Skip hidden core fields
                    if ( in_array( $meta['meta_key'], apply_filters( 'woocommerce_hidden_order_itemmeta', array(
                        '_qty',
                        '_tax_class',
                        '_product_id',
                        '_variation_id',
                        '_line_subtotal',
                        '_line_subtotal_tax',
                        '_line_total',
                        '_line_tax',
                        'method_id',
                        'cost'
                    ) ) ) ) {
                        continue;
                    }

                    // Skip serialised meta
                    if ( is_serialized( $meta['meta_value'] ) ) {
                        continue;
                    }

                    $meta['meta_key']   = rawurldecode( $meta['meta_key'] );
                    $meta['meta_value'] = esc_textarea( rawurldecode( $meta['meta_value'] ) ); // using a <textarea />
                    $meta['meta_id']    = absint( $meta['meta_id'] );

                    echo '<tr data-meta_id="' . esc_attr( $meta['meta_id'] ) . '">
                        <td>
                            <input type="text" name="meta_key[' . $meta['meta_id'] . ']" value="' . esc_attr( $meta['meta_key'] ) . '" />
                            <textarea name="meta_value[' . $meta['meta_id'] . ']">' . $meta['meta_value'] . '</textarea>
                        </td>
                        <td width="1%"><button class="remove_order_item_meta button">&times;</button></td>
                    </tr>';
                }
            }
        ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4"><button class="add_order_item_meta button"><?php _e( 'Add&nbsp;meta', 'woocommerce' ); ?></button></td>
            </tr>
        </tfoot>
    </table>
</div>
