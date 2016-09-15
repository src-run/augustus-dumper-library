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

/**
 * Interface for creating temporary compiled PHP files.
 */
interface CompilerInterface
{
    /**
     * Compile file and return included.
     *
     * @return mixed
     */
    public function compile();

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @return bool
     */
    public function isCompiled();

    /**
     * @return bool
     */
    public function isStale();

    /**
     * @return bool
     */
    public function removeCompiled();
}

/* EOF */
