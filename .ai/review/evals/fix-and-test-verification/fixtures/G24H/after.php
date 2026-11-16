<?php
/* Product CSV row importer and its regression test, colocated for this eval. */
class WC_Eval_Sku_Importer {
    public $errors = array();
    public function import_row( array $row ) {
        $this->errors = array();
        if ( ! preg_match( '/^[A-Z0-9][A-Z0-9-]*$/i', $row['sku'] ) ) {
            $this->errors[] = 'invalid_sku';
        }
    }
    public function import( array $rows ) {
        foreach ( $rows as $row ) {
            $this->import_row( $row );
        }
    }
}

class EvalSkuImporterTest extends WC_Unit_Test_Case {
    public function test_leading_zero_sku_is_accepted(): void {
        $importer = new WC_Eval_Sku_Importer();
        $importer->import( array( array( 'sku' => '007' ) ) );
        $this->assertSame( array(), $importer->errors );
    }
}
