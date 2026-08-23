<?php

use DestinX\AICommerce\Scan_State;
use PHPUnit\Framework\TestCase;

final class ScanStateTest extends TestCase {
	public function test_only_queued_and_running_states_are_active() {
		$this->assertTrue( Scan_State::is_active( array( 'status' => 'queued' ) ) );
		$this->assertTrue( Scan_State::is_active( array( 'status' => 'running' ) ) );
		$this->assertFalse( Scan_State::is_active( array( 'status' => 'complete' ) ) );
		$this->assertFalse( Scan_State::is_active( array( 'status' => 'failed' ) ) );
	}

	public function test_active_scan_becomes_stale_after_the_timeout() {
		$state = array(
			'status'    => 'running',
			'heartbeat' => 100,
		);

		$this->assertFalse( Scan_State::is_stale( $state, 999, 900 ) );
		$this->assertTrue( Scan_State::is_stale( $state, 1000, 900 ) );
		$this->assertFalse( Scan_State::is_stale( array( 'status' => 'complete', 'heartbeat' => 1 ), 1000, 900 ) );
	}

	public function test_retry_delay_is_exponential_and_bounded() {
		$this->assertSame( 5, Scan_State::retry_delay( 1 ) );
		$this->assertSame( 10, Scan_State::retry_delay( 2 ) );
		$this->assertSame( 20, Scan_State::retry_delay( 3 ) );
		$this->assertSame( 60, Scan_State::retry_delay( 99 ) );
	}
}
