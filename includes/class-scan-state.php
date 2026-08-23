<?php
/**
 * Background scan state helpers.
 *
 * @package DestinXAICommerce
 */

namespace DestinX\AICommerce;

/**
 * Provides deterministic scan state decisions without WordPress side effects.
 */
final class Scan_State {
	/**
	 * Whether a state represents unfinished work.
	 *
	 * @param array<string, mixed> $state Scan state.
	 * @return bool
	 */
	public static function is_active( array $state ) {
		return isset( $state['status'] ) && in_array( $state['status'], array( 'queued', 'running' ), true );
	}

	/**
	 * Whether an unfinished scan has exceeded its heartbeat timeout.
	 *
	 * @param array<string, mixed> $state           Scan state.
	 * @param int                  $now             Current Unix timestamp.
	 * @param int                  $timeout_seconds Allowed heartbeat age.
	 * @return bool
	 */
	public static function is_stale( array $state, $now, $timeout_seconds ) {
		if ( ! self::is_active( $state ) ) {
			return false;
		}

		$heartbeat = isset( $state['heartbeat'] ) ? (int) $state['heartbeat'] : 0;
		if ( 0 >= $heartbeat ) {
			return false;
		}

		return (int) $now - $heartbeat >= max( 1, (int) $timeout_seconds );
	}

	/**
	 * Calculate a bounded exponential retry delay.
	 *
	 * @param int $attempt Retry attempt starting from one.
	 * @return int
	 */
	public static function retry_delay( $attempt ) {
		$attempt = max( 1, min( 5, (int) $attempt ) );

		return min( 60, 5 * ( 2 ** ( $attempt - 1 ) ) );
	}
}
