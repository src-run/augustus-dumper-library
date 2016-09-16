<?php

/*
 * This file is part of the `src-run/augustus-compiler-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Compiler;

use Psr\Log\LoggerInterface;
use SR\Compiler\Exception\CompilerException;
use SR\File\Lock\FileLock;
use SR\Log\LoggerAwareTrait;
use SR\Silencer\CallSilencer;
use Symfony\Component\Yaml\Yaml;

/**
 * Create temporary compiled PHP file from YML file.
 */
class YmlCompiler implements CompilerInterface
{
    use LoggerAwareTrait;

    /**
     * @var null|\DateInterval
     */
    private $lifetime;

    /**
     * @var string
     */
    private $inputFile;

    /**
     * @var string
     */
    private $outputFile;

    /**
     * @var mixed
     */
    private $data;

    /**
     * Construct with lifetime of compiled file.
     *
     * @param \string              $file
     * @param \DateInterval|null   $lifetime
     * @param LoggerInterface|null $logger
     */
    public function __construct($file, \DateInterval $lifetime = null, LoggerInterface $logger = null)
    {
        $this->inputFile = $file;
        $this->lifetime = $lifetime === null ? new \DateInterval('P1Y') : $lifetime;

        $realFile = realpath($file);
        $baseName = preg_replace('{\.[a-z]+$}i', '', basename($realFile));

        $this->outputFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.$baseName.'_'.md5($realFile).'.php';

        if ($logger) {
            $this->setLogger($logger);
        }
    }

    /**
     * Compile file and return included.
     *
     * @return mixed
     */
    public function compile()
    {
        if (!$this->isCompiled() || $this->isStale()) {
            $this->compileFile();
        }

        return $this->data = $this->includeFile();
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function isCompiled()
    {
        return file_exists($this->outputFile);
    }

    /**
     * @return bool
     */
    public function isStale()
    {
        if (!file_exists($this->outputFile)) {
            return true;
        }

        $dateTime = new \DateTime();
        $dateDiff = $dateTime->diff(new \DateTime('@'.filemtime($this->outputFile)));

        return $this->intervalToInt($dateDiff) > $this->intervalToInt($this->lifetime);
    }

    /**
     * @param \DateInterval $dateDiff
     *
     * @return int
     */
    private function intervalToInt(\DateInterval $dateDiff)
    {
        $dateDiffString = '';

        foreach (['y', 'm', 'd', 'h', 'i', 's'] as $f) {
            $dateDiffString .= str_pad((string) $dateDiff->format('%'.$f), 2, '0', STR_PAD_LEFT);
        }

        return (int) $dateDiffString;
    }

    /**
     * @return bool
     */
    public function removeCompiled()
    {
        $this->logDebug('Removing compiled file {output} for input file {input}', [
            'input' => $this->inputFile,
            'output' => $this->outputFile,
        ]);

        return @unlink($this->outputFile);
    }

    /**
     * @throws CompilerException
     */
    private function compileFile()
    {
        if (!file_exists($this->inputFile)) {
            throw new CompilerException('Could not read file "%s"', $this->inputFile);
        }

        $lock = $this->compileOutputFileLock();
        $data = Yaml::parse(file_get_contents($this->inputFile), Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);

        $this->compileOutputFileWrite($data, $lock);

        $this->logDebug('Wrote compiled file {output} for input file {input}', [
            'input' => $this->inputFile,
            'output' => $this->outputFile,
        ]);
    }

    /**
     * @return FileLock
     */
    private function compileOutputFileLock()
    {
        $lock = new FileLock(
            $this->outputFile,
            FileLock::LOCK_EXCLUSIVE | FileLock::LOCK_NON_BLOCKING,
            $this->hasLogger() ? $this->getLogger() : null
        );
        $lock->acquire();

        return $lock;
    }

    /**
     * @param mixed    $data
     * @param FileLock $lock
     *
     * @throws CompilerException
     */
    private function compileOutputFileWrite($data, FileLock $lock)
    {
        $silencer = new CallSilencer();
        $silencer->setClosure(function () use ($data, $lock) {
            return fwrite($lock->getResource(), '<?php return '.var_export($data, true).';');
        })->invoke();

        if ($silencer->isResultFalse() || $silencer->hasError()) {
            throw new CompilerException('Could not write compiled file "%s"', $silencer->getError(CallSilencer::ERROR_MESSAGE));
        }

        $lock->release();
    }

    /**
     * @return mixed
     */
    private function includeFile()
    {
        return include $this->outputFile;
    }
}

/* EOF */
