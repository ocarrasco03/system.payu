<?php
/**
 * Webiny Framework (http://www.webiny.com/framework)
 *
 * @copyright Copyright Webiny LTD
 */

namespace Webiny\Component\Crypt\Tests;

use Webiny\Component\Crypt\Crypt;
use Webiny\Component\Crypt\CryptTrait;

class CryptTraitTest extends \PHPUnit_Framework_TestCase
{
    use CryptTrait;

    public function testCrypt()
    {
        $instance = $this->crypt();
        $this->assertInstanceOf(Crypt::class, $instance);
    }

}
