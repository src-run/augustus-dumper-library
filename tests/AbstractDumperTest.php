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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SR\Dumper\Exception\CompilationException;
use SR\Dumper\Exception\InvalidInputException;
use SR\Dumper\Exception\InvalidOutputException;
use SR\Dumper\Model\ResultModel;
use SR\Dumper\YamlDumper;
use SR\File\Lock\FileLock;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \SR\Dumper\AbstractDumper
 */
class AbstractDumperTest extends AbstractTest
{
    public function testConstructionWithLogger()
    {
        $dump = new YamlDumper(self::FIXTURE_VALID_YAML, null, new NullLogger());

        $this->assertInstanceOf(LoggerInterface::class, $dump->getLogger());
    }

    public function testThrowsExceptionOnFileOutputErrorOnWrite()
    {
        $data = new ResultModel(Yaml::parse(file_get_contents(self::FIXTURE_VALID_YAML)));
        $lock = FileLock::create(self::FIXTURE_VALID_YAML);
        $dump = new YamlDumper(self::FIXTURE_VALID_YAML);

        $this->expectException(CompilationException::class);
        $this->expectExceptionMessage('fwrite() expects parameter 1 to be resource');

        $method = $this->getDumperReflectionMethod('tryWrite');
        $method->invoke($dump, $data, $lock);
    }

    public function testThrowsExceptionOnFileOutputErrorOnInclude()
    {
        $dump = new YamlDumper(self::FIXTURE_INVALID_FILE);

        $this->expectException(InvalidOutputException::class);
        $this->expectExceptionMessage('include(): Failed opening');

        $method = $this->getDumperReflectionMethod('tryInclude');
        $method->invoke($dump);
    }

    public function testThrowsExceptionOnFileInputErrorOnRead()
    {
        $this->expectException(InvalidInputException::class);
        $this->expectExceptionMessage('Could not read input file');

        $dump = new YamlDumper(self::FIXTURE_INVALID_FILE);

        $method = $this->getDumperReflectionMethod('tryRead');
        $method->invoke($dump);
    }

    public function testUsesCustomBaseOutputPath()
    {
        $dump = new YamlDumper(self::FIXTURE_VALID_YAML);
        $dump->setOutputBasePath('/tmp/some/path/to/dumps');
        $dump->dump();

        $this->assertDirectoryExists('/tmp/some/path/to/dumps');

        $dump->remove();
        rmdir('/tmp/some/path/to/dumps');
        rmdir('/tmp/some/path/to');
        rmdir('/tmp/some/path');
        rmdir('/tmp/some');
    }
}
