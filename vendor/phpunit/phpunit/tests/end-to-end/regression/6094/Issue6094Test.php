<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TestFixture\Issue6094;

use Exception;
use PHPUnit\Framework\TestCase;

final class Issue6094Test extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        throw new Exception('message');
    }

    public function testOne(): void
    {
        $this->assertTrue(true);
    }
}
