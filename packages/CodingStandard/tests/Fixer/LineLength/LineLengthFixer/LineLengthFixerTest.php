<?php declare(strict_types=1);

namespace Symplify\CodingStandard\Tests\Fixer\LineLength\LineLengthFixer;

use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandardTester\Testing\AbstractCheckerTestCase;

final class LineLengthFixerTest extends AbstractCheckerTestCase
{
    public function test(): void
    {
        $this->doTestFiles([
            __DIR__ . '/Fixture/correct.php.inc',
            __DIR__ . '/Fixture/correct2.php.inc',
            __DIR__ . '/Fixture/correct3.php.inc',
            __DIR__ . '/Fixture/correct4.php.inc',
            __DIR__ . '/Fixture/correct5.php.inc',
            __DIR__ . '/Fixture/correct6.php.inc',
            __DIR__ . '/Fixture/correct7.php.inc',
            __DIR__ . '/Fixture/correct8.php.inc',
            __DIR__ . '/Fixture/correct9.php.inc',
            __DIR__ . '/Fixture/correct10.php.inc',
            __DIR__ . '/Fixture/correct11.php.inc',
            __DIR__ . '/Fixture/correct12.php.inc',
            __DIR__ . '/Fixture/wrong.php.inc',
            __DIR__ . '/Fixture/wrong2.php.inc',
            __DIR__ . '/Fixture/wrong3.php.inc',
            __DIR__ . '/Fixture/wrong4.php.inc',
            __DIR__ . '/Fixture/wrong5.php.inc',
            __DIR__ . '/Fixture/wrong6.php.inc',
            __DIR__ . '/Fixture/wrong7.php.inc',
            __DIR__ . '/Fixture/wrong8.php.inc',
            __DIR__ . '/Fixture/wrong9.php.inc',
            __DIR__ . '/Fixture/wrong10.php.inc',
            __DIR__ . '/Fixture/wrong11.php.inc',
            __DIR__ . '/Fixture/wrong12.php.inc',
            __DIR__ . '/Fixture/wrong13.php.inc',
            __DIR__ . '/Fixture/wrong14.php.inc',
            __DIR__ . '/Fixture/wrong15.php.inc',
            __DIR__ . '/Fixture/wrong16.php.inc',
            __DIR__ . '/Fixture/wrong18.php.inc',
        ]);
    }

    protected function getCheckerClass(): string
    {
        return LineLengthFixer::class;
    }
}
