<?php

namespace ValidFormBuilder;

use PHPUnit\Framework\TestCase;

/**
 * Comprehensive coverage for {@link \ValidFormBuilder\Collection}.
 *
 * Surface covered:
 * - Constructor (with and without initial array)
 * - Add operations: addObject, addObject(toBeginning), addObjectAtPosition, addObjects
 * - Retrieval: getFirst, getLast, getLast($type), random, count, end
 * - Iterator interface: current, next, previous, key, valid, rewind, foreach
 * - Pointer inspection: isFirst, isLast
 * - Collection manipulation: merge, reverse, rebuild, randomize
 * - Search / removal: inCollection (value + class + returnKey), remove, removeRecursive
 * - Seek pointer advance
 */
class CollectionTest extends TestCase
{
    private Collection $collection;

    protected function setUp(): void
    {
        $this->collection = new Collection();
    }

    // --------------------------------------------------------------
    // Constructor
    // --------------------------------------------------------------

    public function testConstructorCreatesEmptyCollectionByDefault(): void
    {
        $collection = new Collection();

        $this->assertSame(0, $collection->count());
    }

    public function testConstructorAcceptsInitialArray(): void
    {
        $collection = new Collection(['a', 'b', 'c']);

        $this->assertSame(3, $collection->count());
        $this->assertSame('a', $collection->getFirst());
        $this->assertSame('c', $collection->getLast());
    }

    public function testConstructorIgnoresNonArrayArgument(): void
    {
        // Passing something that isn't an array should not populate the collection.
        $collection = new Collection('not an array');

        $this->assertSame(0, $collection->count());
    }

    // --------------------------------------------------------------
    // addObject / addObjects / addObjectAtPosition
    // --------------------------------------------------------------

    public function testAddObjectAppendsToEnd(): void
    {
        $this->collection->addObject('a');
        $this->collection->addObject('b');

        $this->assertSame(2, $this->collection->count());
        $this->assertSame('a', $this->collection->getFirst());
        $this->assertSame('b', $this->collection->getLast());
    }

    public function testAddObjectWithBeginningFlagPrependsToStart(): void
    {
        $this->collection->addObject('a');
        $this->collection->addObject('b', true);

        $this->assertSame('b', $this->collection->getFirst());
        $this->assertSame('a', $this->collection->getLast());
    }

    public function testAddObjectsAppendsAllItems(): void
    {
        $this->collection->addObjects(['a', 'b', 'c']);

        $this->assertSame(3, $this->collection->count());
        $this->assertSame('a', $this->collection->getFirst());
        $this->assertSame('c', $this->collection->getLast());
    }

    public function testAddObjectAtPositionInsertsInMiddle(): void
    {
        $this->collection->addObjects(['a', 'c']);
        $this->collection->addObjectAtPosition('b', 1);

        $this->assertSame(['a', 'b', 'c'], $this->drain());
    }

    public function testAddObjectAtPositionZeroInsertsAtStart(): void
    {
        $this->collection->addObjects(['b', 'c']);
        $this->collection->addObjectAtPosition('a', 0);

        $this->assertSame(['a', 'b', 'c'], $this->drain());
    }

    public function testAddObjectAtPositionBeyondEndAppends(): void
    {
        $this->collection->addObject('a');
        $this->collection->addObjectAtPosition('b', 42);

        $this->assertSame(['a', 'b'], $this->drain());
    }

    public function testAddObjectAtPositionEqualToCountAppends(): void
    {
        $this->collection->addObjects(['a', 'b']);
        // Position == count() → falls into the "append" branch (>= count).
        $this->collection->addObjectAtPosition('c', 2);

        $this->assertSame(['a', 'b', 'c'], $this->drain());
    }

    // --------------------------------------------------------------
    // Retrieval: getFirst / getLast / random / count / end
    // --------------------------------------------------------------

    public function testGetFirstAndGetLastReturnNullWhenEmpty(): void
    {
        $this->assertNull($this->collection->getFirst());
        $this->assertNull($this->collection->getLast());
    }

    public function testGetLastWithTypeFiltersByClass(): void
    {
        $a = new \stdClass();
        $b = new Collection();
        $c = new \stdClass();

        $this->collection->addObjects([$a, $b, $c]);

        $this->assertSame($c, $this->collection->getLast(\stdClass::class));
        $this->assertSame($b, $this->collection->getLast(Collection::class));
    }

    public function testGetLastWithTypeReturnsNullWhenNoMatch(): void
    {
        $this->collection->addObjects([new \stdClass(), new \stdClass()]);

        $this->assertNull($this->collection->getLast(\DateTime::class));
    }

    public function testRandomReturnsItemFromCollection(): void
    {
        $items = ['a', 'b', 'c'];
        $this->collection->addObjects($items);

        $this->assertContains($this->collection->random(), $items);
    }

    public function testRandomReturnsNullForEmptyCollection(): void
    {
        $this->assertNull($this->collection->random());
    }

    public function testCountReflectsNumberOfItems(): void
    {
        $this->assertSame(0, $this->collection->count());

        $this->collection->addObject('a');
        $this->assertSame(1, $this->collection->count());

        $this->collection->addObject('b');
        $this->assertSame(2, $this->collection->count());
    }

    public function testEndReturnsLastItemAndAdvancesPointer(): void
    {
        $this->collection->addObjects(['a', 'b', 'c']);

        $this->assertSame('c', $this->collection->end());
        $this->assertTrue($this->collection->isLast());
    }

    // --------------------------------------------------------------
    // Iterator interface
    // --------------------------------------------------------------

    public function testForeachYieldsItemsInOrder(): void
    {
        $this->collection->addObjects(['a', 'b', 'c']);

        $this->assertSame(['a', 'b', 'c'], $this->drain());
    }

    public function testCurrentNextPreviousKey(): void
    {
        $this->collection->addObjects(['a', 'b', 'c']);

        $this->collection->rewind();
        $this->assertSame('a', $this->collection->current());
        $this->assertSame(0, $this->collection->key());

        $this->assertSame('b', $this->collection->next());
        $this->assertSame(1, $this->collection->key());

        $this->assertSame('a', $this->collection->previous());
        $this->assertSame(0, $this->collection->key());
    }

    public function testValidReturnsFalseWhenPointerPastEnd(): void
    {
        $this->collection->addObjects(['a', 'b']);
        $this->collection->rewind();

        $this->assertTrue($this->collection->valid());
        $this->collection->next();
        $this->assertTrue($this->collection->valid());
        $this->collection->next();
        $this->assertFalse($this->collection->valid());
    }

    public function testRewindReturnsCollection(): void
    {
        $result = $this->collection->rewind();

        $this->assertSame($this->collection, $result);
    }

    // --------------------------------------------------------------
    // Pointer inspection
    // --------------------------------------------------------------

    public function testIsFirstAndIsLastReflectPointerPosition(): void
    {
        $this->collection->addObjects(['a', 'b', 'c']);
        $this->collection->rewind();

        $this->assertTrue($this->collection->isFirst());
        $this->assertFalse($this->collection->isLast());

        $this->collection->next();
        $this->assertFalse($this->collection->isFirst());
        $this->assertFalse($this->collection->isLast());

        $this->collection->next();
        $this->assertFalse($this->collection->isFirst());
        $this->assertTrue($this->collection->isLast());
    }

    // --------------------------------------------------------------
    // merge / reverse / rebuild / randomize
    // --------------------------------------------------------------

    public function testMergeAppendsAnotherCollection(): void
    {
        $this->collection->addObjects(['a', 'b']);
        $other = new Collection(['c', 'd']);

        $this->collection->merge($other);

        $this->assertSame(['a', 'b', 'c', 'd'], $this->drain());
    }

    public function testMergeWithEmptyCollectionLeavesOriginalUnchanged(): void
    {
        $this->collection->addObjects(['a', 'b']);
        $this->collection->merge(new Collection());

        $this->assertSame(['a', 'b'], $this->drain());
    }

    public function testMergeWithNonObjectIsIgnored(): void
    {
        $this->collection->addObjects(['a', 'b']);
        $this->collection->merge('not a collection');

        $this->assertSame(['a', 'b'], $this->drain());
    }

    public function testReverseFlipsOrderAndReturnsCollection(): void
    {
        $this->collection->addObjects(['a', 'b', 'c']);
        $result = $this->collection->reverse();

        $this->assertSame($this->collection, $result);
        $this->assertSame(['c', 'b', 'a'], $this->drain());
    }

    public function testRebuildRenumbersAfterGaps(): void
    {
        $this->collection->addObjects(['a', 'b', 'c']);

        // Punch a hole in the internal array directly via reflection.
        $ref = new \ReflectionProperty(Collection::class, 'collection');
        $ref->setAccessible(true);
        $internal = $ref->getValue($this->collection);
        unset($internal[1]);
        $ref->setValue($this->collection, $internal);

        $this->collection->rebuild();

        $this->assertSame([0 => 'a', 1 => 'c'], $this->drainWithKeys());
    }

    public function testRandomizePreservesItemsButMayReorder(): void
    {
        $items = range(1, 100);
        $collection = new Collection($items);

        $collection->randomize();

        $drained = [];
        foreach ($collection as $item) {
            $drained[] = $item;
        }

        $this->assertCount(100, $drained);
        $this->assertEqualsCanonicalizing($items, $drained);
    }

    // --------------------------------------------------------------
    // seek
    // --------------------------------------------------------------

    public function testSeekAdvancesInternalPointer(): void
    {
        $this->collection->addObjects(['a', 'b', 'c', 'd']);

        $this->collection->seek(2);

        $this->assertSame('c', $this->collection->current());
        $this->assertSame(2, $this->collection->key());
    }

    public function testSeekIgnoresNonNumericArgument(): void
    {
        $this->collection->addObjects(['a', 'b', 'c']);
        $this->collection->rewind();

        $this->collection->seek('not a number');

        // Pointer should remain at the start.
        $this->assertSame('a', $this->collection->current());
    }

    // --------------------------------------------------------------
    // inCollection / remove / removeRecursive
    // --------------------------------------------------------------

    public function testInCollectionReturnsTrueWhenObjectFound(): void
    {
        $obj = (object) ['id' => 2];
        $this->collection->addObjects([
            (object) ['id' => 1],
            $obj,
            (object) ['id' => 3],
        ]);

        $this->assertTrue($this->collection->inCollection($obj));
    }

    public function testInCollectionReturnsFalseWhenObjectAbsent(): void
    {
        $this->collection->addObject((object) ['id' => 1]);

        $this->assertFalse($this->collection->inCollection(new \DateTime()));
    }

    public function testInCollectionMatchesByClassName(): void
    {
        $this->collection->addObjects([new \stdClass(), new Collection()]);

        $this->assertTrue($this->collection->inCollection(Collection::class));
    }

    public function testInCollectionWithReturnKeyYieldsFirstMatchAtPositionZero(): void
    {
        $obj = (object) ['id' => 1];
        $this->collection->addObjects([$obj, (object) ['id' => 2]]);

        $this->assertSame(0, $this->collection->inCollection($obj, true));
    }

    public function testInCollectionWithReturnKeyYieldsCorrectPositionForLaterItem(): void
    {
        $target = (object) ['id' => 3];
        $this->collection->addObjects([
            (object) ['id' => 1],
            (object) ['id' => 2],
            $target,
        ]);

        $this->assertSame(2, $this->collection->inCollection($target, true));
    }

    public function testInCollectionWithReturnKeyReturnsFalseWhenAbsent(): void
    {
        $this->collection->addObject((object) ['id' => 1]);

        $this->assertFalse($this->collection->inCollection(new \DateTime(), true));
    }

    public function testRemoveDeletesTheObjectAndRebuildsIndex(): void
    {
        // Use stdClass with distinguishing properties — Collection compares with
        // loose equality (==), so empty stdClass instances would all be equal.
        $a = (object) ['id' => 1];
        $b = (object) ['id' => 2];
        $c = (object) ['id' => 3];
        $this->collection->addObjects([$a, $b, $c]);

        $this->collection->remove($b);

        $this->assertSame(2, $this->collection->count());
        $this->assertSame($a, $this->collection->getFirst());
        $this->assertSame($c, $this->collection->getLast());
    }

    public function testRemoveNonExistentObjectLeavesCollectionIntact(): void
    {
        $this->collection->addObjects([
            (object) ['id' => 1],
            (object) ['id' => 2],
        ]);
        $countBefore = $this->collection->count();

        $this->collection->remove((object) ['id' => 999]);

        $this->assertSame($countBefore, $this->collection->count());
    }

    public function testRemoveRecursiveDropsTopLevelMatchingElementByName(): void
    {
        $form = new ValidForm('test-form');
        $first = $form->addField('first', 'First', ValidForm::VFORM_STRING);
        $second = $form->addField('second', 'Second', ValidForm::VFORM_STRING);

        $collection = new Collection();
        $collection->addObjects([$first, $second]);

        $collection->removeRecursive($first);

        // removeRecursive does not rebuild the index, so drain via foreach which
        // skips holes produced by unset().
        $remaining = [];
        foreach ($collection as $item) {
            $remaining[] = $item->getName();
        }

        $this->assertSame(['second'], $remaining);
    }

    public function testRemoveRecursiveDescendsIntoFieldset(): void
    {
        $form = new ValidForm('test-form');
        $fieldset = $form->addFieldset('Contact');
        $name = $form->addField('name', 'Name', ValidForm::VFORM_STRING);
        $email = $form->addField('email', 'Email', ValidForm::VFORM_EMAIL);

        // `addField` on a form places fields into the last fieldset, so both
        // name and email now live inside `$fieldset`.
        $this->assertTrue($fieldset->hasFields());
        $this->assertSame(2, $fieldset->getFields()->count());

        // Put the fieldset in a fresh Collection and recursively remove a nested field.
        $collection = new Collection();
        $collection->addObject($fieldset);

        $collection->removeRecursive($name);

        // Fieldset should now contain only the email field.
        $remainingNames = [];
        foreach ($fieldset->getFields() as $item) {
            $remainingNames[] = $item->getName();
        }

        $this->assertSame(['email'], $remainingNames);
    }

    // --------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------

    /**
     * Drain the collection into a plain indexed array.
     * @return array<int, mixed>
     */
    private function drain(): array
    {
        $out = [];
        foreach ($this->collection as $item) {
            $out[] = $item;
        }
        return $out;
    }

    /**
     * Drain the collection preserving keys.
     * @return array<int|string, mixed>
     */
    private function drainWithKeys(): array
    {
        $out = [];
        foreach ($this->collection as $key => $item) {
            $out[$key] = $item;
        }
        return $out;
    }
}
