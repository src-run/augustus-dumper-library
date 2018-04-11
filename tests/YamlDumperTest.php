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
 * @covers \SR\Dumper\AbstractDumper
 * @covers \SR\Dumper\YamlDumper
 * @covers \SR\Dumper\Model\ResultModel
 */
class YamlDumperTest extends AbstractTest
{
    public function testCompilation()
    {
        $data = new ResultModel(Yaml::parse(file_get_contents(self::FIXTURE_VALID_YAML)));

        $dump = new YamlDumper(self::FIXTURE_VALID_YAML, new \DateInterval('PT2S'));
        $dump->remove();

        $filePath = $this
            ->getDumperReflectionProperty('output')
            ->getValue($dump);

        $this->assertFalse($dump->hasData());
        $this->assertTrue($dump->isStale());
        $this->assertFileNotExists($filePath);
        $this->assertSame($data->getData(), $dump->dump()->getData());
        $this->assertTrue($dump->hasData());
        $this->assertTrue($dump->getData()->isArray());
        $this->assertFalse($dump->getData()->isString());
        $this->assertSame(count(Yaml::parse(file_get_contents(self::FIXTURE_VALID_YAML))), $dump->getData()->count());
        $this->assertInstanceOf(\ArrayIterator::class, $dump->getData()->getIterator());
        $this->assertInstanceOf(ResultModel::class, $dump->getData());
        $this->assertInternalType('array', $dump->getData()->getData());
        $this->assertFileExists($filePath);
        $this->assertTrue($dump->remove());
        $this->assertFileNotExists($filePath);
        $this->assertSame($data->getData(), $dump->dump()->getData());
        $this->assertFalse($dump->isStale());

        sleep(3);

        $this->assertTrue($dump->isStale());
        $dump->remove();
    }

    public function testThrowsExceptionOnInvalidYaml()
    {
        $dump = new YamlDumper(self::FIXTURE_INVALID_YAML, new \DateInterval('PT2S'));
        $dump->remove();

        $this->expectException(CompilationException::class);
        $this->expectExceptionMessage('Could not parse input file data as YAML');

        $dump->dump();
    }
}
