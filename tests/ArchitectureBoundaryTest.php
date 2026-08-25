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
