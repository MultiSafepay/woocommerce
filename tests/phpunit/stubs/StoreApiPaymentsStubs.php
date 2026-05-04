<?php declare(strict_types=1);

namespace Automattic\WooCommerce\StoreApi\Payments;

if ( ! class_exists( __NAMESPACE__ . '\\PaymentContext', true ) ) {
	/**
	 * Minimal WooCommerce Blocks payment context stub used by the PHPUnit suite.
	 *
	 * Production code type-hints WooCommerce Blocks classes. Unit tests may run without
	 * those classes loaded, so we provide a lightweight replacement.
	 */
	class PaymentContext {
		/**
		 * Stored values for magic access.
		 *
		 * @var array<string, mixed>
		 */
		private $data;

		/**
		 * @param array<string, mixed> $data
		 */
		public function __construct( array $data ) {
			$this->data = $data;
		}

		/**
		 * @param string $name
		 * @return mixed
		 */
		public function __get( string $name ) {
			return $this->data[ $name ] ?? null;
		}
	}
}

if ( ! class_exists( __NAMESPACE__ . '\\PaymentResult', true ) ) {
	/**
	 * Minimal WooCommerce Blocks payment result stub used by the PHPUnit suite.
	 */
	class PaymentResult {
	}
}
