<?php declare(strict_types=1);

use MultiSafepay\WooCommerce\Utils\Logger;

class Test_Logger extends WP_UnitTestCase {

    public function test_logs_are_created_for_all_levels(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->exactly(6))->method('log');
        $logger->log_emergency('Test emergency');
        $logger->log_alert('Test alert');
        $logger->log_critical('Test critical');
        $logger->log_error('Test error');
        $logger->log_warning('Test warning');
        $logger->log_notice('Test notice');
    }

    public function test_info_and_debug_logs_are_created_when_debug_mode_is_true(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->exactly(2))->method('log');

        update_option('multisafepay_debugmode', true);

        $logger->log_info('Test info');
        $logger->log_debug('Test debug');
    }

    public function test_info_and_debug_logs_are_not_created_when_debug_mode_is_false(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->never())->method('log');

        update_option('multisafepay_debugmode', false);

        $logger->log_info('Test info');
        $logger->log_debug('Test debug');
    }

    public function test_log_emergency(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'emergency',
                'This is a test log message',
                ['source' => 'multisafepay']
            );
        $logger->log_emergency('This is a test log message');
    }

    public function test_log_alert(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'alert',
                'This is a test log message',
                ['source' => 'multisafepay']
            );
        $logger->log_alert('This is a test log message');
    }

    public function test_log_critical(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'critical',
                'This is a test log message',
                ['source' => 'multisafepay']
            );
        $logger->log_critical('This is a test log message');
    }

    public function test_log_error(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'error',
                'This is a test log message',
                ['source' => 'multisafepay']
            );
        $logger->log_error('This is a test log message');
    }

    public function test_log_warning(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'warning',
                'This is a test log message',
                ['source' => 'multisafepay']
            );
        $logger->log_warning('This is a test log message');
    }

    public function test_log_notice(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'notice',
                'This is a test log message',
                ['source' => 'multisafepay']
            );
        $logger->log_notice('This is a test log message');
    }

    public function test_log_info(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'info',
                'This is a test log message',
                ['source' => 'multisafepay']
            );
        update_option('multisafepay_debugmode', true);
        $logger->log_info('This is a test log message');
    }

    public function test_log_debug(): void {
        $loggerMock = $this->createMock(WC_Logger::class);
        $logger = new Logger($loggerMock);
        $loggerMock->expects($this->once())
            ->method('log')
            ->with(
                'debug',
                'This is a test log message',
                ['source' => 'multisafepay']
            );
        update_option('multisafepay_debugmode', true);
        $logger->log_debug('This is a test log message');
    }
}
