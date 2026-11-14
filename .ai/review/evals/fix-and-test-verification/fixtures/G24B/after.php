<?php
/* Catalog pagination helper and its regression test, colocated for this eval. */
function wc_catalog_page_args( $paged ) {
    return array( 'post_type' => 'product', 'posts_per_page' => 1, 'orderby' => 'ID', 'order' => 'ASC', 'catalog_page' => max( 1, $paged ) );
}
function wc_catalog_page( $paged ) { return new WP_Query( wc_catalog_page_args( $paged ) ); }

class CatalogPageTest extends WC_Unit_Test_Case {
    public function test_second_page(): void {
        $ids = self::factory()->post->create_many( 2, array( 'post_type' => 'product', 'post_status' => 'publish' ) );
        $query = wc_catalog_page( 2 );
        $this->assertSame( 2, wc_catalog_page_args( 2 )['catalog_page'] );
    }
}
