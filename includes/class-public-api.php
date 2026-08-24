<?php
/**
 * Public read-only extension API.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Gives add-ons stable read-only access to the Free plugin engine.
 */
final class Public_API {
	/**
	 * Version of this extension contract, independent from the plugin version.
	 */
	const VERSION = '1.0.0';

	/**
	 * Audit snapshot repository.
	 *
	 * @var Audit_Repository
	 */
	private $repository;

	/**
	 * Background scan service.
	 *
	 * @var Background_Audit
	 */
	private $background;

	/**
	 * Constructor.
	 *
	 * @param Audit_Repository $repository Stored audit results.
	 * @param Background_Audit $background Background scan state.
	 */
	public function __construct( Audit_Repository $repository, Background_Audit $background ) {
		$this->repository = $repository;
		$this->background = $background;
	}

	/**
	 * Return the extension contract version.
	 *
	 * @return string
	 */
	public function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the installed Free plugin version.
	 *
	 * @return string
	 */
	public function get_plugin_version() {
		return DXAIC_VERSION;
	}

	/**
	 * Return metadata for the visible, completed snapshot.
	 *
	 * @return array<string, int|string>
	 */
	public function get_snapshot_metadata() {
		return $this->repository->get_snapshot_metadata();
	}

	/**
	 * Return aggregate values for the visible snapshot.
	 *
	 * @return array<string, int>
	 */
	public function get_summary() {
		return $this->repository->get_summary();
	}

	/**
	 * Return one bounded page of visible audit results.
	 *
	 * @param int                  $page     Page number.
	 * @param int                  $per_page Results per page, capped at 100.
	 * @param array<string, mixed> $filters  Free engine filters.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_results( $page = 1, $per_page = 20, array $filters = array() ) {
		$page     = max( 1, (int) $page );
		$per_page = max( 1, min( 100, (int) $per_page ) );

		return $this->repository->get_page( $page, $per_page, $filters );
	}

	/**
	 * Return the normalized full-catalog scan state.
	 *
	 * @return array<string, mixed>
	 */
	public function get_scan_state() {
		return $this->background->get_state();
	}
}
