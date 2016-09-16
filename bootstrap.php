<?php

/*
 * This file is part of the `src-run/augustus-dumper-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

$aliases = [
    'SR\Dumper\YamlDumper' => 'SR\Compiler\YmlCompiler',
    'SR\Dumper\DumperInterface' => 'SR\Compiler\CompilerInterface'
];

foreach ($aliases as $activeFqcn => $aliasedFqcn) {
    class_alias($activeFqcn, $aliasedFqcn);
}

/* EOF */
