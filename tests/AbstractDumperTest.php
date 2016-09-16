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
use SR\Dumper\YamlDumper;
use SR\File\Lock\FileLock;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \SR\Dumper\AbstractDumper
 */
class AbstractDumperTest extends AbstractTest
{
    public function testCompilation()
    {
        $data = Yaml::parse(file_get_contents(self::FIXTURE_VALID_YAML));

        $dump = new YamlDumper(self::FIXTURE_VALID_YAML, new \DateInterval('PT2S'));
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
    }

    public function testConstructionWithLogger()
    {
        $dump = new YamlDumper(self::FIXTURE_VALID_YAML, null, new NullLogger());

        $this->assertInstanceOf(LoggerInterface::class, $dump->getLogger());
    }

    public function testThrowsExceptionOnFileOutputErrorOnWrite()
    {
        $data = Yaml::parse(file_get_contents(self::FIXTURE_VALID_YAML));
        $lock = new FileLock(self::FIXTURE_VALID_YAML);
        $dump = new YamlDumper(self::FIXTURE_VALID_YAML);

        $this->expectException(CompilationException::class);
        $this->expectExceptionMessage('fwrite() expects parameter 1 to be resource');

        $method = $this->getDumperReflectionMethod('tryWrite');
        $method->invoke($dump, '<?php return '.var_export($data, true).';', $lock);
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
}

/* EOF */
