<?php
namespace Oportunidades;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait Trait_Singleton {
    /**
     * Plugin instance storage.
     *
     * @var static
     */
    protected static $instance = null;

    /**
     * Retrieve singleton instance.
     *
     * @return static
     */
    public static function instance() {
        if ( null === static::$instance ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserializing.
     */
    public function __wakeup() {
        throw new \RuntimeException( 'Cannot unserialize singleton' );
    }
}
