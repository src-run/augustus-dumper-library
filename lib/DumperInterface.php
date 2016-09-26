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
use SR\Log\LoggerAwareInterface;

/**
 * Interface for creating dumped PHP files from various initial formats.
 */
interface DumperInterface extends LoggerAwareInterface
{
    /**
     * Construct dumper instance, given a file path and optional lifetime date interval and logger interface.
     *
     * @param \string              $file     The input file to dump
     * @param \DateInterval|null   $lifetime The output file fresh lifetime
     * @param LoggerInterface|null $logger   An optional logger instance
     */
    public function __construct($file, \DateInterval $lifetime = null, LoggerInterface $logger = null);

    /**
     * Returns true if an input file or output (dumped) has been read and is non-null.
     *
     * @return bool
     */
    public function hasData();

    /**
     * Returns the data originally read from the input file and dumped to the output (dumped) file.
     *
     * @return mixed
     */
    public function getData();

    /**
     * Remove compiled output file.
     *
     * @return bool
     */
    public function remove();

    /**
     * Returns true if an output (dumped) file exists.
     *
     * @return bool
     */
    public function isDumped();

    /**
     * Returns true if output (dumped) file does not exist or is older than the configured lifetime.
     *
     * @return bool
     */
    public function isStale();

    /**
     * Dump the input file to output (dumped) file, if output does not exist or is stale, and return file data.
     *
     * @return mixed
     */
    public function dump();
}
