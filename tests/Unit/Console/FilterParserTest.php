<?php

declare(strict_types=1);

namespace McoreServices\TeamleaderSDK\Tests\Unit\Console;

use InvalidArgumentException;
use McoreServices\TeamleaderSDK\Console\Support\FilterParser;
use PHPUnit\Framework\TestCase;

final class FilterParserTest extends TestCase
{
    public function test_key_value_pairs(): void
    {
        $this->assertSame(['status' => 'active', 'term' => 'Acme NV'], FilterParser::parse(['status=active', 'term=Acme NV']));
    }

    public function test_lists_with_brackets(): void
    {
        $this->assertSame(['tags' => ['vip', 'b2b']], FilterParser::parse(['tags[]=vip', 'tags[]=b2b']));
    }

    public function test_objects_with_dots(): void
    {
        $this->assertSame(
            ['customer' => ['type' => 'company', 'id' => 'abc']],
            FilterParser::parse(['customer.type=company', 'customer.id=abc'])
        );
    }

    public function test_values_are_cast(): void
    {
        $this->assertSame(
            ['a' => true, 'b' => false, 'c' => null, 'd' => 12, 'e' => -1.5, 'f' => '007', 'g' => '123'],
            FilterParser::parse(['a=true', 'b=false', 'c=null', 'd=12', 'e=-1.5', 'f=007', 'g="123"'])
        );
    }

    public function test_an_equals_sign_in_the_value_is_kept(): void
    {
        $this->assertSame(['term' => 'a=b'], FilterParser::parse(['term=a=b']));
    }

    public function test_an_expression_without_equals_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot read --filter=status');

        FilterParser::parse(['status']);
    }
}
