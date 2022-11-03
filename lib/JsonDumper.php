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
use SR\Dumper\Model\ResultModel;
use SR\Silencer\CallSilencerFactory;

/**
 * Implementation for creating dumped PHP files from JSON file formats.
 */
class JsonDumper extends AbstractDumper
{
    /**
     * Parse the input file data to the expected format that should be cached in the output (dumped) file.
     *
     * @param string $data The input file data
     *
     * @throws CompilationException
     */
    protected function parseInputData(string $data): ResultModel
    {
        $return = CallSilencerFactory::create(function () use ($data) {
            return json_decode($data, true);
        }, function ($return) {
            return null !== $return;
        })->invoke();

        if (!$return->isValid() || $return->hasError()) {
            throw new CompilationException('Could not parse input file data as JSON %s', $this->input);
        }

        return new ResultModel($return->getReturn());
    }
}
