<?php

/*
 * This file is part of the `src-run/augustus-dumper-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Dumper;

use Psr\Log\LoggerInterface;
use SR\Dumper\Exception\CompilationException;
use SR\Dumper\Exception\InvalidInputException;
use SR\Dumper\Exception\InvalidOutputException;
use SR\Dumper\Model\ResultModel;
use SR\File\Lock\FileLock;
use SR\File\Lock\FileLockInterface;
use SR\Log\LoggerAwareTrait;
use SR\Silencer\CallSilencerFactory;

/**
 * Implementation for creating dumped PHP files from various initial formats.
 */
abstract class AbstractDumper implements DumperInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    protected $input;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var string
     */
    protected $output;

    /**
     * @var string
     */
    protected $outputBasePath;

    /**
     * @var null|\DateInterval
     */
    protected $lifetime;

    /**
     * Construct dumper instance, given a file path and optional lifetime date interval and logger interface.
     *
     * @param string               $file     The input file to dump
     * @param \DateInterval|null   $lifetime The output file fresh lifetime
     * @param LoggerInterface|null $logger   An optional logger instance
     */
    public function __construct(string $file, \DateInterval $lifetime = null, LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
        $this->setupLifetime($lifetime);
        $this->setupInput($file);
        $this->setupOutput($file);
    }

    /**
     * @param string|null $outputBasePath
     */
    public function setOutputBasePath(string $outputBasePath = null)
    {
        $this->outputBasePath = $outputBasePath;
        $this->setupOutput($this->input);
    }

    /**
     * Returns true if an input file or output (dumped) has been read and is non-null.
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return null !== $this->data;
    }

    /**
     * Returns the data originally read from the input file and dumped to the output (dumped) file.
     *
     * @return ResultModel
     */
    public function getData(): ResultModel
    {
        return $this->data;
    }

    /**
     * Returns true if output (dumped) file is successfully removed.
     *
     * @return bool
     */
    public function remove(): bool
    {
        $return = CallSilencerFactory::create(function () {
            return unlink($this->output);
        }, function ($result, $error = null) {
            return true === $result && (null === $error || 0 === count($error));
        })->invoke();

        if (!$return->isValid()) {
            return false;
        }

        $this->logDebug('Removed output {output}: dumped for input {input}.', [
            'input' => $this->input,
            'output' => $this->output,
        ]);

        return true;
    }

    /**
     * Returns true if an output (dumped) file exists.
     *
     * @return bool
     */
    public function isDumped(): bool
    {
        return file_exists($this->output);
    }

    /**
     * Returns true if output (dumped) file does not exist or is older than the configured lifetime.
     *
     * @return bool
     */
    public function isStale(): bool
    {
        if (!file_exists($this->output)) {
            return true;
        }

        $nowDateTime = new \DateTime();
        $outDateDiff = $nowDateTime->diff(new \DateTime('@'.filemtime($this->output)));

        return $this->isGreaterThanLifetime($outDateDiff);
    }

    /**
     * Dump the input file to output (dumped) file, if output does not exist or is stale, and return file data.
     *
     * @throws InvalidInputException  If an error occurs with the input file
     * @throws InvalidOutputException If an error occurs with the output file
     * @throws CompilationException   If dump compilation fails
     *
     * @return ResultModel
     */
    public function dump(): ResultModel
    {
        if (!$this->isDumped() || $this->isStale()) {
            $this->remove();
            $this->tryDump();
        }

        $this->data = $data = $this->tryInclude();

        return $data;
    }

    /**
     * Parse the input file data to the expected format that should be cached in the output (dumped) file.
     *
     * @param string $data A data string read from the input file
     *
     * @return ResultModel
     */
    abstract protected function parseInputData(string $data): ResultModel;

    /**
     * @throws InvalidInputException
     * @throws InvalidOutputException
     * @throws CompilationException
     *
     * @return self
     */
    protected function tryDump(): self
    {
        $data = $this->parseInputData($this->tryRead());
        $lock = $this->tryLock();
        $this->tryWrite($data, $lock);

        $this->logDebug('Wrote output {output}: dumped for input {input}.', [
            'input' => $this->input,
            'output' => $this->output,
        ]);

        return $this;
    }

    /**
     * @return FileLockInterface
     */
    protected function tryLock(): FileLockInterface
    {
        $lock = FileLock::create($this->output, FileLock::LOCK_EXCLUSIVE | FileLock::LOCK_NON_BLOCKING);
        $lock->setLogger($this->logger);
        $lock->acquire();

        return $lock;
    }

    /**
     * @param ResultModel       $data
     * @param FileLockInterface $lock
     *
     * @throws CompilationException
     */
    protected function tryWrite(ResultModel $data, FileLockInterface $lock)
    {
        $return = CallSilencerFactory::create(function () use ($data, $lock) {
            return fwrite($lock->getResource(), '<?php return '.var_export($data, true).';');
        }, function ($return) {
            return false !== $return;
        })->invoke();

        if (!$return->isValid() || $return->hasError()) {
            $this->logDebug('Could not write dumped output file: {file}', [
                'file' => $return->getErrorMessage(),
            ]);

            throw new CompilationException('Could not write dumped output file: %s', $return->getErrorMessage());
        }
    }

    /**
     * @throws InvalidOutputException
     *
     * @return ResultModel
     */
    protected function tryInclude(): ResultModel
    {
        $return = CallSilencerFactory::create(function () {
            return include $this->output;
        })->invoke();

        if ($return->isFalse() || $return->hasError()) {
            throw new InvalidOutputException('Could not include dumped output file: %s', $return->getErrorMessage());
        }

        return $return->getReturn();
    }

    /**
     * @throws InvalidInputException
     *
     * @return string
     */
    protected function tryRead(): string
    {
        $return = CallSilencerFactory::create(function () {
            return file_get_contents($this->input);
        }, function ($return) {
            return is_string($return);
        })->invoke();

        if (!$return->isValid() || $return->hasError()) {
            throw new InvalidInputException('Could not read input file: %s', $return->getErrorMessage());
        }

        return $return->getReturn();
    }

    /**
     * @param string $file
     */
    protected function setupInput(string $file)
    {
        $this->input = $file;
    }

    /**
     * @param \DateInterval|null $lifetime
     */
    protected function setupLifetime(\DateInterval $lifetime = null)
    {
        if (!$lifetime) {
            $lifetime = new \DateInterval('P1Y');
        }

        $this->lifetime = $lifetime;
    }

    /**
     * @param string $file
     */
    protected function setupOutput(string $file)
    {
        $this->output = vsprintf('%s%s_dumped-%s_%s_%s.php', [
            $this->outputBasePath ?: sys_get_temp_dir(),
            DIRECTORY_SEPARATOR,
            pathinfo($file, PATHINFO_EXTENSION),
            hash('sha256', $file),
            pathinfo(realpath($file), PATHINFO_FILENAME),
        ]);

        if (!is_dir($path = pathinfo($this->output, PATHINFO_DIRNAME))) {
            mkdir($path, 0777, true);
        }
    }

    /**
     * @param \DateInterval $outputInterval
     *
     * @return bool
     */
    protected function isGreaterThanLifetime(\DateInterval $outputInterval): bool
    {
        $toComparable = function (\DateInterval $interval) {
            $comparable = '';
            foreach (['y', 'm', 'd', 'h', 'i', 's'] as $f) {
                $comparable .= str_pad((string) $interval->format('%'.$f), 2, '0', STR_PAD_LEFT);
            }

            return (int) $comparable;
        };

        return $toComparable($outputInterval) > $toComparable($this->lifetime);
    }
}
