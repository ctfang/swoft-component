<?php declare(strict_types=1);


namespace Swoft\Bean\Concern;

use Swoft\Bean\Exception\PrototypeException;

/**
 * Class Prototype
 *
 * @since 2.0
 */
trait Prototype
{
    /**
     * Get instance from container
     *
     * @return static
     * @throws PrototypeException
     */
    private static function __instance(): self
    {
        try {
            $prototype = bean(static::class);
        } catch (\Throwable $e) {
            throw new PrototypeException($e->getMessage());
        }

        return $prototype;
    }
}