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
use SR\Dumper\Model\ResultModel;

/**
 * @covers \SR\Dumper\JsonDumper
 */
class JsonDumperTest extends AbstractTest
{
    public function testCompilation()
    {
        $data = new ResultModel(json_decode(file_get_contents(self::FIXTURE_VALID_JSON), true));

        $dump = new JsonDumper(self::FIXTURE_VALID_JSON, new \DateInterval('PT2S'));
        $dump->remove();

        $this->assertFalse($dump->hasData());
        $this->assertTrue($dump->isStale());
        $this->assertEquals($data, $dump->dump());
        $this->assertEquals($data, $dump->getData());
        $this->assertTrue($dump->hasData());
        $this->assertTrue($dump->getData()->isArray());
        $this->assertFalse($dump->isStale());
        $this->assertTrue($dump->remove());
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
