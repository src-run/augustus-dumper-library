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

use SR\Log\LoggerAwareInterface;

/**
 * Interface for creating temporary compiled PHP files.
 */
interface CompilerInterface extends LoggerAwareInterface
{
    /**
     * Compile file and return included.
     *
     * @return mixed|false
     */
    public function compile();

    /**
     * Check if input file is compiled.
     *
     * @return bool
     */
    public function isCompiled();

    /**
     * Check if output file is stale.
     *
     * @return bool
     */
    public function isStale();

    /**
     * Remove compiled output file.
     *
     * @return bool
     */
    public function removeCompiled();

    /**
     * Get output file include data.
     *
     * @return mixed
     */
    public function getData();
}

/* EOF */
