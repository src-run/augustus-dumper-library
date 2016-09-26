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
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Implementation for creating dumped PHP files from YAML file formats.
 */
class YamlDumper extends AbstractDumper
{
    /**
     * Parse the input file data to the expected format that should be cached in the output (dumped) file.
     *
     * @param mixed $data The input file data
     *
     * @throws CompilationException
     *
     * @return mixed
     */
    protected function parseInputData($data)
    {
        try {
            return Yaml::parse($data, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
        } catch (ParseException $e) {
            throw new CompilationException('Could not parse input file data as YAML %s', $this->input);
        }
    }
}
