<?php

namespace Walletable\Tests;

use Walletable\Tests\Models\Walletable;

class ManagerTest extends TestBench
{
    public function testExample()
    {
        Walletable::create([
            'name' => 'Test User',
            'email' => 'olawale.tester@gmail.com'
        ]);

        $this->assertCount(1, Walletable::all());
    }
}
