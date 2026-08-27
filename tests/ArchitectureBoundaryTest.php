<?php

use PHPUnit\Framework\TestCase;

final class ArchitectureBoundaryTest extends TestCase {
	/**
	 * Return every PHP file shipped as part of the Free runtime.
	 *
	 * @return array<int, string>
	 */
	private function get_runtime_files() {
		$root  = dirname( __DIR__ );
		$files = array(
			$root . '/destinx-ai-commerce.php',
			$root . '/uninstall.php',
		);

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$root . '/includes',
				FilesystemIterator::SKIP_DOTS
			)
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
				$files[] = $file->getPathname();
			}
		}

		sort( $files );

		return $files;
	}

	public function test_free_runtime_contains_no_commercial_or_remote_service_code() {
		$forbidden_literals = array(
			'DestinX\\AICommercePro',
			'DXAICP_',
			'dxaicp_',
			'configcraftsuite.com',
			'/wp-json/dxls/',
			'license_key',
			'entitlement',
			'check-update',
		);

		foreach ( $this->get_runtime_files() as $file ) {
			$source = file_get_contents( $file );

			$this->assertIsString( $source, 'The runtime source must be readable: ' . $file );

			foreach ( $forbidden_literals as $literal ) {
				$this->assertStringNotContainsString(
					$literal,
					$source,
					'The Free runtime must not contain commercial or Pro-only code: ' . $file
				);
			}

			$this->assertDoesNotMatchRegularExpression(
				'/\bwp_remote_(?:get|post|request|head)\s*\(/i',
				$source,
				'The Free runtime must remain local and must not call a remote service: ' . $file
			);
		}
	}

	public function test_free_runtime_contains_no_dormant_commercial_controls() {
		$forbidden_patterns = array(
			'/\b(?:trial|trialware)\b/i',
			'/\b(?:activate|validate|verify)[_-]?license\b/i',
			'/\b(?:premium|pro)[_-]?(?:feature|module|implementation|code)\b/i',
			'/\bupgrade[_-]?url\b/i',
		);

		foreach ( $this->get_runtime_files() as $file ) {
			$source = file_get_contents( $file );

			$this->assertIsString( $source, 'The runtime source must be readable: ' . $file );
			foreach ( $forbidden_patterns as $pattern ) {
				$this->assertDoesNotMatchRegularExpression(
					$pattern,
					$source,
					'The Free runtime must not ship dormant commercial controls: ' . $file
				);
			}
		}
	}

	public function test_admin_assets_contain_no_remote_resource_or_service_urls() {
		$root = dirname( __DIR__ );
		foreach ( array( $root . '/assets/admin.js', $root . '/assets/admin.css' ) as $file ) {
			$source = file_get_contents( $file );

			$this->assertIsString( $source, 'The runtime asset must be readable: ' . $file );
			$this->assertDoesNotMatchRegularExpression(
				'#(?:https?:)?//#i',
				$source,
				'The Free admin assets must not load a remote resource or service: ' . $file
			);
		}
	}

	public function test_release_metadata_is_aligned_and_readme_is_bounded() {
		$root   = dirname( __DIR__ );
		$main   = file_get_contents( $root . '/destinx-ai-commerce.php' );
		$readme = file_get_contents( $root . '/readme.txt' );
		$pot    = file_get_contents( $root . '/languages/destinx-ai-commerce.pot' );

		$this->assertIsString( $main );
		$this->assertIsString( $readme );
		$this->assertIsString( $pot );
		$this->assertMatchesRegularExpression( '/^ \* Version:\s+([^\s]+)$/m', $main );
		preg_match( '/^ \* Version:\s+([^\s]+)$/m', $main, $header_match );
		preg_match( "/define\( 'DXAIC_VERSION', '([^']+)' \);/", $main, $constant_match );
		preg_match( '/^Stable tag:\s+([^\s]+)$/m', $readme, $stable_match );

		$this->assertNotEmpty( $constant_match );
		$this->assertNotEmpty( $stable_match );
		$this->assertSame( $header_match[1], $constant_match[1] );
		$this->assertSame( $header_match[1], $stable_match[1] );
		$this->assertStringContainsString( 'Project-Id-Version: DestinX AI Commerce for WooCommerce ' . $header_match[1], $pot );
		$this->assertMatchesRegularExpression( '/^Contributors:\s+destinx$/m', $readme );
		$this->assertLessThan( 10000, strlen( $readme ), 'WordPress.org readme.txt must remain below 10,000 bytes.' );

		preg_match( '/^Tags:\s*(.+)$/m', $readme, $tags_match );
		$this->assertNotEmpty( $tags_match );
		$this->assertLessThanOrEqual( 5, count( array_filter( array_map( 'trim', explode( ',', $tags_match[1] ) ) ) ) );
	}

	public function test_free_plugin_does_not_claim_an_external_update_channel() {
		$source = file_get_contents( dirname( __DIR__ ) . '/destinx-ai-commerce.php' );

		$this->assertIsString( $source );
		$this->assertDoesNotMatchRegularExpression(
			'/^\s*\*\s*Update URI:/mi',
			$source,
			'The Free plugin update channel must be owned by WordPress.org.'
		);
	}

	public function test_public_extension_api_is_read_only() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-public-api.php' );

		$this->assertIsString( $source );
		preg_match_all( '/public\s+function\s+([a-zA-Z0-9_]+)\s*\(/', $source, $matches );

		$actual = array_values( array_diff( $matches[1], array( '__construct' ) ) );
		$expected = array(
			'get_plugin_version',
			'get_result',
			'get_results',
			'get_scan_state',
			'get_snapshot_metadata',
			'get_summary',
			'get_version',
		);

		sort( $actual );
		sort( $expected );

		$this->assertSame(
			$expected,
			$actual,
			'The Free extension API may expose read-only audit data only.'
		);
	}
}
