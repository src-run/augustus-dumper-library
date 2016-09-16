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

use SR\Dumper\Exception\CompilationException;
use SR\Silencer\CallSilencer;

/**
 * Implementation for creating dumped PHP files from JSON file formats.
 */
class JsonDumper extends AbstractDumper
{
    /**
     * Parse the input file data to the expected format that should be cached in the output (dumped) file.
     *
     * @param mixed $data The input file data.
     *
     * @return mixed
     */
    protected function parseInputData($data)
    {
        $silencer = new CallSilencer();
        $silencer->setClosure(function () use ($data) {
            return json_decode($data, true);
        });
        $silencer->setValidator(function ($return) {
            return $return !== null;
        });
        $silencer->invoke();

        if (!$silencer->isResultValid() || $silencer->hasError()) {
            throw new CompilationException('Could not parse input file data as JSON %s', $this->input);
        }

        return $silencer->getResult();
    }
}

/* EOF */
