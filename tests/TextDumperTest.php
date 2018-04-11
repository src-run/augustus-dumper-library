<?php

/*
 * This file is part of the `src-run/augustus-dumper-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Dumper\Tests;

use SR\Dumper\Model\ResultModel;
use SR\Dumper\TextDumper;

/**
 * @covers \SR\Dumper\AbstractDumper
 * @covers \SR\Dumper\TextDumper
 * @covers \SR\Dumper\Model\ResultModel
 */
class TextDumperTest extends AbstractTest
{
    public function testCompilation()
    {
        $data = new ResultModel(file_get_contents(self::FIXTURE_VALID_TEXT));

        $dump = new TextDumper(self::FIXTURE_VALID_TEXT, new \DateInterval('PT2S'));
        $dump->remove();

        $this->assertFalse($dump->hasData());
        $this->assertTrue($dump->isStale());
        $this->assertSame($data->getData(), $dump->dump()->getData());
        $this->assertTrue($dump->hasData());
        $this->assertFalse($dump->getData()->isArray());
        $this->assertTrue($dump->getData()->isString());
        $this->assertSame(mb_strlen(file_get_contents(self::FIXTURE_VALID_TEXT)), $dump->getData()->count());
        $this->assertInstanceOf(\ArrayIterator::class, $dump->getData()->getIterator());
        $this->assertInstanceOf(ResultModel::class, $dump->getData());
        $this->assertInternalType('string', $dump->getData()->getData());
        $this->assertFalse($dump->isStale());
        $this->assertTrue($dump->remove());

        $dump->remove();
    }
}
