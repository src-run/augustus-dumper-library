<?php

/*
 * This file is part of the `src-run/augustus-dumper-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Dumper\Tests;

use PHPUnit\Framework\TestCase;
use SR\Dumper\YamlDumper;

/**
 * Abstract test case instance for dumper tests.
 */
abstract class AbstractTest extends TestCase
{
    public const FIXTURE_VALID_YAML = __DIR__ . '/Fixtures/test-array-valid.yml';

    public const FIXTURE_VALID_JSON = __DIR__ . '/Fixtures/test-array-valid.json';

    public const FIXTURE_VALID_TEXT = __DIR__ . '/Fixtures/test-string-valid.txt';

    public const FIXTURE_INVALID_FILE = __DIR__ . '/Fixtures/test/file/does/not/exist.yml';

    public const FIXTURE_INVALID_YAML = __DIR__ . '/Fixtures/test-array-invalid.yml';

    public const FIXTURE_INVALID_JSON = __DIR__ . '/Fixtures/test-array-invalid.json';

    /**
     * @param string $property
     *
     * @return \ReflectionProperty
     */
    protected function getDumperReflectionProperty($property)
    {
        $reflectionClass = new \ReflectionClass(YamlDumper::class);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }

    /**
     * @param string $property
     *
     * @return \ReflectionMethod
     */
    protected function getDumperReflectionMethod($method)
    {
        $reflectionClass = new \ReflectionClass(YamlDumper::class);
        $reflectionMethod = $reflectionClass->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod;
    }
}
