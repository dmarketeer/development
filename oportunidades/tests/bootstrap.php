<?php
// Basic bootstrap placeholder for PHPUnit.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

require_once __DIR__ . '/../includes/class-importer.php';
require_once __DIR__ . '/../includes/class-database.php';

use Oportunidades\Includes\Importer;
use Oportunidades\Includes\Database;

class ImporterTest extends WP_UnitTestCase {
    public function test_import_without_title_throws_exception() {
        $db = $this->createMock( Database::class );
        $importer = new Importer( $db );

        $this->expectException( \Exception::class );
        $importer->import( [ 'oportunidades' => [ [ 'titulo' => '' ] ] ], 'test' );
    }
}
