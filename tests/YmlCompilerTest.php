<?php

/*
 * This file is part of the `src-run/augustus-compiler-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\File\Object\Tests\Component\Compiler;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SR\Compiler\YmlCompiler;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \SR\Compiler\YmlCompiler
 */
class YmlCompilerTest extends \PHPUnit_Framework_TestCase
{
    public function testCompilation()
    {
        $file = realpath(__DIR__.'/Fixtures/array.yml');
        $data = Yaml::parse(file_get_contents($file));

        $compiler = new YmlCompiler($file, new \DateInterval('PT2S'));
        $compiler->removeCompiled();

        $rc = new \ReflectionClass(YmlCompiler::class);
        $rp = $rc->getProperty('outputFile');
        $rp->setAccessible(true);
        $filePath = $rp->getValue($compiler);

        $this->assertTrue($compiler->isStale());
        $this->assertFileNotExists($filePath);
        $this->assertSame($data, $compiler->compile());
        $this->assertSame($data, $compiler->getData());
        $this->assertFileExists($filePath);
        $this->assertTrue($compiler->removeCompiled());
        $this->assertFileNotExists($filePath);
        $this->assertSame($data, $compiler->compile());
        $this->assertFalse($compiler->isStale());

        sleep(3);

        $this->assertTrue($compiler->isStale());
    }

    /**
     * @expectedException \SR\Compiler\Exception\CompilerException
     */
    public function testThrowsExceptionOnNonExistantInputFile()
    {
        $file = __DIR__.'/Fixtures/invalid-file.yml';

        $compiler = new YmlCompiler($file);
        $compiler->compile();
    }

    public function testConstructionWithLogger()
    {
        $file = __DIR__.'/Fixtures/array.yml';

        $compiler = new YmlCompiler($file, null, new NullLogger());
        $this->assertInstanceOf(LoggerInterface::class, $compiler->getLogger());
    }
}

/* EOF */
