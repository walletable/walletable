<?php

namespace Walletable\Tests\Mutation;

use Walletable\Internals\Mutation\MutationInterface;
use Walletable\Internals\Mutation\MutatorManager;
use Walletable\Tests\TestBench;
use Walletable\Tests\Mutation\TestMutatorOne;
use Walletable\Tests\Mutation\TestMutatorTwo;
use Walletable\Tests\Mutation\TestNamedMutation;

class MutatorManagerTest extends TestBench
{
    public function testMutator()
    {
        $manager = new MutatorManager();

        $manager->mutator(TestMutation::class, new TestMutatorTwo(), MutatorManager::PRIORITY_HIGH);
        $manager->mutator(TestMutation::class, new TestMutatorOne(), MutatorManager::PRIORITY_NORMAL);

        $text = $manager->mutate(new TestMutation('Text:'))->value();
        $text1 = $manager->mutate(new TestMutation('Text:'))->value();

        $this->assertSame('Text: two one', $text);
        $this->assertSame('Text: two one', $text1);
    }

    public function testClosureMutator()
    {
        $manager = new MutatorManager();

        $manager->mutator(TestMutation::class, function ($mutation) {
            $mutation->setValue($mutation->value() . ' two');
        }, MutatorManager::PRIORITY_HIGH);
        $manager->mutator(TestMutation::class, function ($mutation) {
            $mutation->setValue($mutation->value() . ' one');
        }, MutatorManager::PRIORITY_NORMAL);

        $text = $manager->mutate(new TestMutation('Text:'))->value();
        $text1 = $manager->mutate(new TestMutation('Text:'))->value();

        $this->assertSame('Text: two one', $text);
        $this->assertSame('Text: two one', $text1);
    }

    public function testMutationExtras()
    {
        $manager = new MutatorManager();

        $manager->mutator(TestMutation::class, new TestMutatorOne(), 1);
        $manager->mutator(TestMutation::class, function ($mutation, string $extra2, string $extra1) {
            $mutation->setValue($mutation->value() . $extra2);
        }, 2);
        $manager->mutator(TestMutation::class, new TestMutatorWithExtra(), 3);

        $text = $manager->mutate($mutation = new TestMutation('Text:', [
            'extra1' => ' three',
            'extra2' => ' four',
        ]))->value();

        $this->assertTrue($mutation->hasExtras());

        $this->assertSame('Text: three four one', $text);
    }

    public function testNamedMutator()
    {
        $manager = new MutatorManager();

        $manager->mutator('test.text', new TestMutatorTwo(), MutatorManager::PRIORITY_HIGH);
        $manager->mutator('test.text', new TestMutatorOne(), MutatorManager::PRIORITY_NORMAL);

        $text = $manager->mutate(new TestNamedMutation('test.text', 'Text:'))->value();
        $text1 = $manager->mutate(new TestNamedMutation('test.text', 'Text:'))->value();

        $this->assertSame('Text: two one', $text);
        $this->assertSame('Text: two one', $text1);
    }

    public function testWrapperHooksMutator()
    {
        $manager = new MutatorManager();

        $manager->mutator(TestMutation::class, new TestMutatorTwo(), MutatorManager::PRIORITY_HIGH);
        $manager->mutator(TestMutation::class, new TestMutatorOne(), MutatorManager::PRIORITY_NORMAL);

        $text = $manager->mutate((new TestMutation('Text:'))->setAfter(function ($mutation) {
            $mutation->setValue($mutation->value() . ')');
        })->setBefore(function ($mutation) {
            $mutation->setValue('(' . $mutation->value());
        }))->value();

        $this->assertSame('(Text: two one)', $text);
    }

    public function testWrapperHooksMutatorWithMethod()
    {
        $manager = new MutatorManager();

        $manager->mutator(TestMutationWrapperMethod::class, new TestMutatorTwo(), MutatorManager::PRIORITY_HIGH);
        $manager->mutator(TestMutationWrapperMethod::class, new TestMutatorOne(), MutatorManager::PRIORITY_NORMAL);

        $text = $manager->mutate(new TestMutationWrapperMethod('Text:'))->value();

        $this->assertSame('(Text: two one)', $text);
    }

    public function testMutationStopped()
    {
        $manager = new MutatorManager();

        $manager->mutator(TestMutation::class, new TestMutatorOne(), 1);
        $manager->mutator(TestMutation::class, function (MutationInterface $mutation, string $extra2, string $extra1) {
            $mutation->stop();
            $mutation->setValue($mutation->value() . $extra2);
        }, 2);
        $manager->mutator(TestMutation::class, new TestMutatorWithExtra(), 3);

        $text = $manager->mutate($mutation = new TestMutation('Text:', [
            'extra1' => ' three',
            'extra2' => ' four',
        ]))->value();

        $this->assertTrue($mutation->hasExtras());

        $this->assertSame('Text: three four', $text);
    }
}
