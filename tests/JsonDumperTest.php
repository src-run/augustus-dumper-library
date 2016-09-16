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

use SR\Dumper\Exception\CompilationException;
use SR\Dumper\JsonDumper;

/**
 * @covers \SR\Dumper\JsonDumper
 */
class JsonDumperTest extends AbstractTest
{
    public function testCompilation()
    {
        $data = json_decode(file_get_contents(self::FIXTURE_VALID_JSON), true);

        $dump = new JsonDumper(self::FIXTURE_VALID_JSON, new \DateInterval('PT2S'));
        $dump->remove();

        $filePath = $this
            ->getDumperReflectionProperty('output')
            ->getValue($dump);

        $this->assertFalse($dump->hasData());
        $this->assertTrue($dump->isStale());
        $this->assertFileNotExists($filePath);
        $this->assertSame($data, $dump->dump());
        $this->assertSame($data, $dump->getData());
        $this->assertTrue($dump->hasData());
        $this->assertFileExists($filePath);
        $this->assertTrue($dump->remove());
        $this->assertFileNotExists($filePath);
        $this->assertSame($data, $dump->dump());
        $this->assertFalse($dump->isStale());

        sleep(3);

        $this->assertTrue($dump->isStale());
        $dump->remove();
    }

    public function testThrowsExceptionOnInvalidYaml()
    {
        $dump = new JsonDumper(self::FIXTURE_INVALID_JSON, new \DateInterval('PT2S'));
        $dump->remove();

        $this->expectException(CompilationException::class);
        $this->expectExceptionMessage('Could not parse input file data as JSON');

        $dump->dump();
    }
}

/* EOF */
