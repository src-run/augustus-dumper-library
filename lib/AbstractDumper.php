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
use SR\File\Lock\FileLock;
use SR\Log\LoggerAwareTrait;
use SR\Silencer\CallSilencer;

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
     * @var null|\DateInterval
     */
    protected $lifetime;

    /**
     * Construct dumper instance, given a file path and optional lifetime date interval and logger interface.
     *
     * @param \string              $file     The input file to dump
     * @param \DateInterval|null   $lifetime The output file fresh lifetime
     * @param LoggerInterface|null $logger   An optional logger instance
     */
    public function __construct($file, \DateInterval $lifetime = null, LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
        $this->setOutputLifetime($lifetime);
        $this->setInputFileName($file);
        $this->setOutputFileName($file);
    }

    /**
     * Parse the input file data to the expected format that should be cached in the output (dumped) file.
     *
     * @param mixed $data A data string read from the input file
     *
     * @return mixed
     */
    abstract protected function parseInputData($data);

    /**
     * Returns true if an input file or output (dumped) has been read and is non-null.
     *
     * @return bool
     */
    public function hasData()
    {
        return $this->data !== null;
    }

    /**
     * Returns the data originally read from the input file and dumped to the output (dumped) file.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns true if output (dumped) file is successfully removed.
     *
     * @return bool
     */
    public function remove()
    {
        $silencer = new CallSilencer();
        $silencer->setClosure(function () {
            return unlink($this->output);
        });
        $silencer->invoke();

        if (!$silencer->isResultTrue() || $silencer->hasError()) {
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
    public function isDumped()
    {
        return file_exists($this->output);
    }

    /**
     * Returns true if output (dumped) file does not exist or is older than the configured lifetime.
     *
     * @return bool
     */
    public function isStale()
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
     * @return mixed
     */
    public function dump()
    {
        if (!$this->isDumped() || $this->isStale()) {
            $this->remove();
            $this->tryDump();
        }

        $this->data = $data = $this->tryInclude();

        return $data;
    }

    /**
     * @throws InvalidInputException
     * @throws InvalidOutputException
     * @throws CompilationException
     *
     * @return $this
     */
    protected function tryDump()
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
     * @return FileLock
     */
    protected function tryLock()
    {
        $lock = new FileLock($this->output, FileLock::LOCK_EXCLUSIVE | FileLock::LOCK_NON_BLOCKING, $this->getLogger());
        $lock->acquire();

        return $lock;
    }

    /**
     * @param mixed    $data
     * @param FileLock $lock
     *
     * @throws CompilationException
     */
    protected function tryWrite($data, FileLock $lock)
    {
        $silencer = new CallSilencer();
        $silencer->setClosure(function () use ($data, $lock) {
            return fwrite($lock->getResource(), '<?php return '.var_export($data, true).';');
        });
        $silencer->setValidator(function ($return) {
            return $return !== false;
        });
        $silencer->invoke();

        if (!$silencer->isResultValid() || $silencer->hasError()) {
            $this->logDebug('Could not write dumped output file: %s', $silencer->getError());

            throw new CompilationException('Could not write dumped output file: %s', $silencer->getError());
        }
    }

    /**
     * @throws InvalidOutputException
     *
     * @return mixed
     */
    protected function tryInclude()
    {
        $silencer = new CallSilencer();
        $silencer->setClosure(function () {
            return include $this->output;
        });
        $silencer->invoke();

        if ($silencer->isResultFalse() || $silencer->hasError()) {
            throw new InvalidOutputException('Could not include dumped output file: %s', $silencer->getError());
        }

        return $silencer->getResult();
    }

    /**
     * @throws InvalidInputException
     *
     * @return string
     */
    protected function tryRead()
    {
        $silencer = new CallSilencer();
        $silencer->setClosure(function () {
            return file_get_contents($this->input);
        });
        $silencer->setValidator(function ($return) {
            return is_string($return);
        });
        $silencer->invoke();

        if (!$silencer->isResultValid() || $silencer->hasError()) {
            throw new InvalidInputException('Could not read input file: %s', $silencer->getError(CallSilencer::ERROR_MESSAGE));
        }

        return $silencer->getResult();
    }

    /**
     * @param string $file
     */
    protected function setInputFileName($file)
    {
        $this->input = realpath($file);
    }

    /**
     * @param \DateInterval|null $lifetime
     */
    protected function setOutputLifetime(\DateInterval $lifetime = null)
    {
        if (!$lifetime) {
            $lifetime = new \DateInterval('P1Y');
        }

        $this->lifetime = $lifetime;
    }

    /**
     * @param string $file
     */
    protected function setOutputFileName($file)
    {
        $realPath = realpath($file);
        $baseName = preg_replace('{\.[a-z]+$}i', '', basename($realPath));

        $this->output = sys_get_temp_dir().DIRECTORY_SEPARATOR.$baseName.'_'.md5($file).'.php';
    }

    /**
     * @param \DateInterval $outputInterval
     *
     * @return bool
     */
    protected function isGreaterThanLifetime(\DateInterval $outputInterval)
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

/* EOF */
