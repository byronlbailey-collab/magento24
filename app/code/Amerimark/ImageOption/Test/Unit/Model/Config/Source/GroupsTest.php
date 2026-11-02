<?php
/**
 * Copyright Amerimark. All rights reserved.
 */
declare(strict_types=1);

namespace Amerimark\ImageOption\Test\Unit\Model\Config\Source;

use Amerimark\ImageOption\Model\Config\Source\Groups;
use Amerimark\ImageOption\Model\Group;
use Amerimark\ImageOption\Model\ResourceModel\Group\Collection;
use Amerimark\ImageOption\Model\ResourceModel\Group\CollectionFactory;
use PHPUnit\Framework\TestCase;

class GroupsTest extends TestCase
{
    public function testInactiveGroupsAreMarkedNotHidden(): void
    {
        $active = $this->createPartialMock(Group::class, []);
        $active->setIdFieldName('group_id');
        $active->setData(['group_id' => 1, 'name' => 'Logos', 'is_active' => 1]);
        $inactive = $this->createPartialMock(Group::class, []);
        $inactive->setIdFieldName('group_id');
        $inactive->setData(['group_id' => 2, 'name' => 'Old', 'is_active' => 0]);

        $collection = $this->createMock(Collection::class);
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$active, $inactive]));
        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $options = (new Groups($factory))->toOptionArray();
        $this->assertSame('1', $options[0]['value']);
        $this->assertSame('Logos', $options[0]['label']);
        $this->assertStringContainsString('inactive', (string)$options[1]['label']);
    }
}
