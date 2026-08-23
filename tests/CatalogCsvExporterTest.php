<?php

use DestinX\AICommerce\Catalog_Csv_Exporter;
use PHPUnit\Framework\TestCase;

final class CatalogCsvExporterTest extends TestCase {
	/**
	 * @dataProvider dangerous_cells
	 */
	public function test_spreadsheet_formulas_are_neutralized( $cell ) {
		$this->assertSame( "'" . $cell, Catalog_Csv_Exporter::spreadsheet_safe_value( $cell ) );
	}

	public function dangerous_cells() {
		return array(
			'equals'            => array( '=2+3' ),
			'plus'              => array( '+SUM(A1:A2)' ),
			'minus'             => array( '-10+20' ),
			'at'                => array( '@SUM(A1:A2)' ),
			'leading space'     => array( ' =2+3' ),
			'leading tab'       => array( "\t=2+3" ),
			'leading line feed' => array( "\n-2+3" ),
		);
	}

	public function test_plain_text_is_unchanged_and_null_bytes_are_removed() {
		$this->assertSame( 'Trail shoe', Catalog_Csv_Exporter::spreadsheet_safe_value( 'Trail shoe' ) );
		$this->assertSame( 'Safe text', Catalog_Csv_Exporter::spreadsheet_safe_value( "Safe\0 text" ) );
	}
}
