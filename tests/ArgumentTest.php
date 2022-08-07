<?php

namespace Walletable\Tests;

use InvalidArgumentException;
use stdClass;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Argument;
use Walletable\Tests\Models\Walletable;

class ArgumentTest extends TestBench
{
    public function testArgument()
    {
        $data = new ActionData(
            'Wellcome',
            200,
            true,
            '234',
            4.5,
            [4, 5],
            function () {
                # code...
            },
            '89099876',
            Walletable::create([
                'name' => 'Olawale Ilesanmi',
                'email' => 'olawale@olawale.com',
            ])
        );



        $this->assertSame('Wellcome', (new Argument($data, 0))->value());

        $this->assertInstanceOf(Argument::class, $data->argument(0)->required());
        $this->assertInstanceOf(Argument::class, $data->argument(0)->notEmpty());
        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('str'));
        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('string'));
        $this->assertInstanceOf(Argument::class, $data->argument(1)->type('int'));
        $this->assertInstanceOf(Argument::class, $data->argument(1)->type('integer'));
        $this->assertInstanceOf(Argument::class, $data->argument(2)->type('bool'));
        $this->assertInstanceOf(Argument::class, $data->argument(2)->type('boolean'));
        $this->assertInstanceOf(Argument::class, $data->argument(3)->type('num'));
        $this->assertInstanceOf(Argument::class, $data->argument(3)->type('numeric'));
        $this->assertInstanceOf(Argument::class, $data->argument(4)->type('num'));
        $this->assertInstanceOf(Argument::class, $data->argument(4)->type('numeric'));
        $this->assertInstanceOf(Argument::class, $data->argument(5)->type('array'));
        $this->assertInstanceOf(Argument::class, $data->argument(6)->type('closure'));
        $this->assertInstanceOf(Argument::class, $data->argument(7)->type('digits'));

        $this->assertInstanceOf(Argument::class, $data->argument(8)->isA(Walletable::class));

        $this->assertInstanceOf(Argument::class, $data->argument(1)->between(191, 201));
        $this->assertInstanceOf(Argument::class, $data->argument(1)->min(191));
        $this->assertInstanceOf(Argument::class, $data->argument(1)->max(201));
    }

    public function testStringException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'ActionData accepts only string values as argument 1.'
        );

        $data = new ActionData(
            new stdClass()
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('string'));
    }

    public function testIntegerException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'ActionData accepts only integer values as argument 1.'
        );

        $data = new ActionData(
            new stdClass()
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('integer'));
    }

    public function testBooleanException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'ActionData accepts only boolean values as argument 1.'
        );

        $data = new ActionData(
            new stdClass()
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('boolean'));
    }

    public function testNumericException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'ActionData accepts only numeric values as argument 1.'
        );

        $data = new ActionData(
            new stdClass()
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('numeric'));
    }

    public function testArrayException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'ActionData accepts only array as argument 1.'
        );

        $data = new ActionData(
            new stdClass()
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('array'));
    }

    public function testClosureException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'ActionData accepts only Closure as argument 1.'
        );

        $data = new ActionData(
            new stdClass()
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('closure'));
    }

    public function testDigitException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'ActionData accepts only digits values as argument 1.'
        );

        $data = new ActionData(
            new stdClass()
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('digits'));
    }

    public function testBetweenException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument 0 must be between 100 and 150.'
        );

        $data = new ActionData(
            200
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->between(100, 150));
    }

    public function testMinException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument 0 must be at least 250.'
        );

        $data = new ActionData(
            200
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->min(250));
    }

    public function testMaxException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument 0 may not be greater than 100.'
        );

        $data = new ActionData(
            200
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->max(100));
    }

    public function testIsAException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Argument 1 must be an instance of Walletable\Internals\Argument but integer given for ActionData'
        );

        $data = new ActionData(
            200
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->isA(Argument::class));
    }

    public function testNotEmptyException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Empty argument 1 for ActionData'
        );

        $data = new ActionData(
            ''
        );

        $this->assertInstanceOf(Argument::class, $data->argument(0)->notEmpty());
    }

    public function testRequiredException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Missing argument 2 for ActionData'
        );

        $data = new ActionData(
            ''
        );

        $this->assertInstanceOf(Argument::class, $data->argument(1)->required());
    }
}
