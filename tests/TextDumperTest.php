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
use SR\Dumper\Model\ResultModel;
use SR\Dumper\YamlDumper;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \SR\Dumper\TextDumper
 */
class TextDumperTest extends AbstractTest
{
    public function testCompilation()
    {
        $data = new ResultModel(file_get_contents(self::FIXTURE_VALID_TEXT));

        $dump = new YamlDumper(self::FIXTURE_VALID_TEXT, new \DateInterval('PT2S'));
        $dump->remove();

        $filePath = $this
            ->getDumperReflectionProperty('output')
            ->getValue($dump);

        $this->assertFalse($dump->hasData());
        $this->assertTrue($dump->isStale());
        $this->assertFileNotExists($filePath);
        $this->assertEquals($data, $dump->dump());
        $this->assertEquals($data, $dump->getData());
        $this->assertTrue($dump->hasData());
        $this->assertTrue($dump->getData()->isString());
        $this->assertFileExists($filePath);
        $this->assertTrue($dump->remove());
        $this->assertFileNotExists($filePath);
        $this->assertEquals($data, $dump->dump());
        $this->assertFalse($dump->isStale());
        $dump->remove();
    }
}