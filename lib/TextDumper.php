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

use SR\Dumper\Model\ResultModel;

/**
 * Implementation for creating dumped PHP files from YAML file formats.
 */
class TextDumper extends AbstractDumper
{
    /**
     * @param string $data The input file data
     *
     * @return ResultModel
     */
    protected function parseInputData(string $data): ResultModel
    {
        return new ResultModel($data);
    }
}
