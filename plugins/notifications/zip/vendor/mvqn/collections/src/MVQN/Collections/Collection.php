<?php
declare(strict_types=1);

namespace MVQN\Collections;

/**
 * Class Collection
 *
 * @package MVQN\Collections
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 */
class Collection implements \JsonSerializable, \Countable, \Iterator
{
    /**
     * @var Collectible[] $elements
     */
    protected $elements;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var int
     */
    private $position;

    // -----------------------------------------------------------------------------------------------------------------

    #region METHODS: ( __construct, __toString )

    /**
     * Collection constructor.
     *
     * @param string $type
     * @param Collectible[]|null $elements
     * @throws \Exception
     */
    public function __construct(string $type, ?array $elements = [])
    {
        if($elements === null)
            $elements = [];

        if(!is_subclass_of($type, Collectible::class, true))
            throw new \Exception("[MVQN\Collections\Collection] The specified type: '$type' must extend '".
                Collectible::class."'!");

        $this->type = $type;
        //$this->elements = [];
        $this->pushMany($elements);

        $this->position = 0;
    }

    /**
     * Overrides the default string representation of the class.
     *
     * @return string Returns a JSON representation of this Model.
     */
    public function __toString()
    {
        // Return the array as a JSON string.
        return json_encode($this, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    #endregion

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return Collectible[]
     */
    public function toArray(): array
    {
        return $this->elements;
    }




    // -----------------------------------------------------------------------------------------------------------------

    #region METHODS: ( for JsonSerializable )

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed Data to be serialized by <b>json_encode</b>, which can be of any type except a resource.
     */
    public function jsonSerialize()
    {
        // Returns an array of all Model properties.
        return $this->elements;
    }

    #endregion

    #region METHODS: ( for Countable )

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->elements);
    }

    #endregion

    #region METHODS: ( for Iterator )

    /**
     * Moves the iterator cursor to the beginning.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @return Collectible Returns the currently iterated item.
     */
    public function current(): Collectible
    {
        return $this->elements[$this->position];
    }

    /**
     * @return int Returns the current position as the iterator key.
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Moves the iterator cursor to the next position.
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @return bool Returns true if there is a valid element at the current position, otherwise false.
     */
    public function valid(): bool
    {
        return isset($this->elements[$this->position]);
    }

    #endregion

    // -----------------------------------------------------------------------------------------------------------------

    #region METHODS: ( elements, at, first, last, all, every, slice )

    /**
     * @return Collectible[]
     */
    public function elements(): array
    {
        return $this->elements !== null ? $this->elements : [];
    }

    /**
     * @param int $index
     * @return Collectible|null
     * @throws \Exception
     */
    public function at(int $index): ?Collectible
    {
        return $this->elements[$this->validIndex($index)];
    }

    /**
     * @return Collectible|null
     */
    public function first(): ?Collectible
    {
        if(!$this->hasIndex(0))
            return null;

        return $this->elements[0];
    }

    /**
     * @return Collectible|null
     * @throws \Exception
     */
    public function last(): ?Collectible
    {
        return $this->elements[$this->validIndex($this->count() - 1)];
    }

    /**
     * @return Collection
     * @throws \Exception
     */
    public function all(): Collection
    {
        return new Collection($this->type, $this->elements);
    }

    /**
     * @param array $range
     * @return Collection
     * @throws \Exception
     */
    public function every(array $range): Collection
    {
        if(!$range)
            throw new \Exception("[MVQN\Collections\Collection] The range: '".
                json_encode($range, JSON_UNESCAPED_SLASHES)."' is invalid!");

        $collection = [];

        foreach($range as $index)
            $collection[] = $this->elements[$this->validIndex($index)];

        return new Collection($this->type, $collection);
    }

    /**
     * @param int $index
     * @param int $count
     * @return Collection
     * @throws \Exception
     */
    public function slice(int $index, int $count): Collection
    {
        $range = range($index, $index + ($count - 1));
        return $this->every($range);
    }


    public function merge(Collection $collection): Collection
    {
        if($this->type() !== $collection->type())
            throw new \Exception("The merging Collection type '{$collection->type()}' must match the original ".
                "Collection type '{$this->type()}'!");

        return new Collection($this->type, array_unique(array_merge($this->elements(), $collection->elements())));
    }



    #endregion

    // -----------------------------------------------------------------------------------------------------------------

    #region METHODS: ( push, pushMany, unshift, unshiftMany, insert, insertMany )

    /**
     * @param Collectible|null $element
     * @return Collection
     * @throws \Exception
     */
    public function push(?Collectible $element): Collection
    {
        $this->pushMany([ $element ]);
        return $this;
    }


    /**
     * Appends the specified elements to the end of the Collection.
     *
     * @param Collectible[] $elements An array of Collectible objects.
     * @return Collection Returns the current Collection after the appended values.
     * @throws \Exception Throws an exception if there were any issues appending the elements.
     */
    public function pushMany(array $elements): Collection
    {
        foreach($elements as $element) {


            // IF the item is an array...
            if (is_array($element))
            {
                // THEN attempt to instantiate it before appending it!
                $element = new $this->type($element);

                if (get_class($element) != $this->type && !is_subclass_of($element, $this->type, true))
                    throw new \Exception("[MVQN\Collections\Collection] The element type: '".
                        get_class($element)."' must match or extend '" .
                        $this->type . "'!");

                $this->elements[] = $element;
            } else
            // IF the item is an object...
            if (is_object($element))
            {
                if (get_class($element) != $this->type && !is_subclass_of($element, $this->type, true))
                    throw new \Exception("[MVQN\Collections\Collection] The element type: '".
                        get_class($element)."' must match or extend '" .
                        $this->type . "'!");

                // THEN it does not need to be instantiated, simply append it!
                $this->elements[] = $element;
            }
            else
            {
                if (get_class($element) != $this->type && !is_subclass_of($element, $this->type, true))
                    throw new \Exception("[MVQN\Collections\Collection] The element type: '".
                        get_class($element) . "' must match or extend '" . $this->type . "'!");

                // OTHERWISE, simply add the type!
                $this->elements[] = $elements;
            }
        }

        //$this->elements += $this->validElements($elements);
        return $this;
    }

    /**
     * @param Collectible|null $element
     * @return Collection
     * @throws \Exception
     */
    public function unshift(?Collectible $element): Collection
    {
        $this->unshiftMany([ $element ]);
        return $this;
    }

    /**
     * @param array $elements
     * @return Collection
     * @throws \Exception
     */
    public function unshiftMany(array $elements): Collection
    {
        foreach($this->validElements($elements) as $element)
            array_unshift($this->elements, $element);

        return $this;
    }

    /**
     * @param int $index
     * @param Collectible|null $element
     * @return Collection
     * @throws \Exception
     */
    public function insert(int $index, ?Collectible $element): Collection
    {
        return $this->insertMany($index, [ $element ]);
    }

    /**
     * @param int $index
     * @param Collectible[] $elements
     * @return Collection
     * @throws \Exception
     */
    public function insertMany(int $index, array $elements): Collection
    {
        array_splice($this->elements, $index, 0, $this->validElements($elements));
        return $this;
    }

    /**
     * @param int $index
     * @return Collection
     * @throws \Exception
     */
    public function remove(int $index): Collection
    {
        return $this->removeMany($index, 1);
    }

    /**
     * @param int $index
     * @param int $count
     * @return Collection
     * @throws \Exception
     */
    public function removeMany(int $index, int $count): Collection
    {
        for($i = $index; $i < $index + $count; $i++)
            unset($this->elements[$this->validIndex($i)]);

        $this->elements = array_values($this->elements());

        return $this;
    }

    #endregion

    // -----------------------------------------------------------------------------------------------------------------

    #region METHODS: ( pop, popMany, shift, shiftMany, delete, deleteMany )

    /**
     * @return Collectible|null
     * @throws \Exception
     */
    public function pop(): ?Collectible
    {
        return $this->popMany(1)->first();
    }

    /**
     * @param int $count
     * @return Collection
     * @throws \Exception
     */
    public function popMany(int $count): Collection
    {
        if($count > $this->count())
            throw new \Exception("[MVQN\Collections\Collection] The count '$count' is more than the number of ".
                "elements in this Collection!");

        if($count === $this->count())
            return $this->all();

        $elements = [];

        for($i = 0; $i < $count; $i++)
            $elements[] = array_pop($this->elements);

        return new Collection($this->type, $elements);
    }

    /**
     * @return Collectible|null
     * @throws \Exception
     */
    public function shift(): ?Collectible
    {
        return $this->shiftMany(1)->first();
    }

    /**
     * @param int $count
     * @return Collection
     * @throws \Exception
     */
    public function shiftMany(int $count): Collection
    {
        if($count > $this->count())
            throw new \Exception("[MVQN\Collections\Collection] The count '$count' is more than the number of ".
                "elements in this Collection!");

        if($count === $this->count())
            return $this->all();

        $elements = [];

        for($i = 0; $i < $count; $i++)
            $elements[] = array_shift($this->elements);

        return new Collection($this->type, $elements);
    }

    /**
     * @param Collectible $element
     * @param int|null $index
     * @return Collection
     * @throws \Exception
     */
    public function delete(Collectible $element, ?int &$index = null): Collection
    {
        $collection = $this->deleteMany([$element], $indices);
        $index = $indices !== null ? $indices[0] : null;

        return $collection;
    }

    /**
     * @param Collectible[] $elements
     * @param array|null $indices
     * @return Collection
     * @throws \Exception
     */
    public function deleteMany(array $elements, ?array &$indices = null): Collection
    {
        foreach($this->elements as $index => $element)
        {
            if(!is_subclass_of($element, $this->type, true) && !is_a($element, $this->type))
                throw new \Exception("[MVQN\Collections\Collection] The element type: '".
                    get_class($element)."' must match or extend '". $this->type."'!");

            // IF the element exists in the Collection...
            if(!empty(array_intersect([$element], $elements)))
            {
                // THEN add its index to the list of deleted indices.
                $indices[] = $index;

                // And then unset the value.
                unset($this->elements[$index]);
            }
        }

        // Reindex the array!
        $this->elements = array_values($this->elements);

        return $this;
    }

    #endregion

    // -----------------------------------------------------------------------------------------------------------------

    #region METHODS: ( each )

    /**
     * @param callable $handler
     * @return Collection
     * @throws \Exception
     */
    public function each(callable $handler): Collection
    {
        $results = [];

        // Loop through each element of the collection...
        foreach($this->elements as $index => $element)
        {
            // Run the handler callback on the current element and append the result to the results array.
            $results[] = $this->validElements([$handler($element, $index)])[0];
        }

        // Validation should have already been performed, so we should be safe to simply assign the new array.
        $this->elements = $results;

        // Return this Collection for chaining methods!
        return $this;
    }

    #endregion

    // -----------------------------------------------------------------------------------------------------------------

    #region METHODS: ( find, where, whereAll, whereAny )

    /**
     * @param callable $evaluator
     * @return Collection
     * @throws \Exception
     */
    public function find(callable $evaluator): Collection
    {
        // Initialize a collection of matches.
        $matches = [];

        if($this->elements !== null)
        {
            // Loop through each element of the collection...
            foreach ($this->elements as $element) {
                // Run the evaluator callback on the current element...
                if ($evaluator($element))
                    // AND add the result to the collection of matches if the evaluator returns true.
                    $matches[] = $element;
            }
        }

        // Return the collection of matches, even if it is empty!
        return new Collection($this->type, $matches);
    }

    /**
     * @param string $property
     * @param $value
     * @return Collection
     * @throws \Exception
     */
    public function where(string $property, $value): Collection
    {
        return $this->whereAll([ $property => $value ]);
    }

    /**
     * @param Collectible[] $comparisons
     * @return Collection
     * @throws \Exception
     */
    public function whereAll(array $comparisons): Collection
    {
        foreach($comparisons as $property => $value)
        {
            if(!property_exists($this->type, $property))
                throw new \Exception("[MVQN\Collections\Collection] The comparison could not be run, as the property '".
                    "$property' does not exist on the Collectible '{$this->type}'");
        }

        return $this->find(
            function ($current) use ($comparisons)
            {
                foreach ($comparisons as $property => $value)
                {
                    $getter = "get".ucfirst($property);

                    //if(!method_exists($current, $getter))
                    //    throw new CollectionException("Cannot compare a private/protected property directly and no ".
                    //    "'{$current}->{$getter}()' method could be found!");

                    // TODO: Handle code to check for private/protected properties ???

                    if ($current->$getter() !== $value)
                        return false;
                }

                return true;
            }
        );
    }

    /**
     * @param Collectible[] $comparisons
     * @return Collection
     * @throws \Exception
     */
    public function whereAny(array $comparisons): Collection
    {
        foreach($comparisons as $property => $value)
        {
            if(!property_exists($this->type, $property))
                throw new \Exception("[MVQN\Collections\Collection] The comparison could not be run, as the property '".
                    "$property' does not exist on the Collectible '{$this->type}'");
        }

        return $this->find(
            function ($current) use ($comparisons)
            {
                foreach ($comparisons as $property => $value)
                {
                    $getter = "get".ucfirst($property);

                    //if(!method_exists($current, $getter))
                    //    throw new CollectionException("Cannot compare a private/protected property directly and no ".
                    //        "'{$current}->{$getter}()' method could be found!");

                    // TODO: Handle code to check for private/protected properties ???

                    if ($current->$getter() === $value)
                        return true;
                }

                return false;
            }
        );
    }

    #endregion

    // -----------------------------------------------------------------------------------------------------------------

    #region METHODS: ( clear )

    /**
     * @return Collection
     */
    public function clear(): Collection
    {
        $this->elements = [];
        return $this;
    }

    #endregion

    // -----------------------------------------------------------------------------------------------------------------

    #region METHODS: ( hasIndex, hasElement, validIndex, validElements )

    /**
     * @param int $index
     * @return bool
     */
    public function hasIndex(int $index): bool
    {
        return ($index < count($this->elements));
    }

    /**
     * @param Collectible $collectible
     * @return bool
     */
    public function hasElement(Collectible $collectible): bool
    {
        foreach($this->elements as $element)
        {
            if($element === $collectible)
                return true;
        }

        return false;
    }

    /**
     * @param int $index
     * @return int
     * @throws \Exception
     */
    public function validIndex(int $index): int
    {
        if(!$this->hasIndex($index))
            throw new \Exception("[MVQN\Collections\Collection] The index: '$index' is invalid!");

        return $index;
    }


    /**
     * @param Collectible[] $elements
     * @return Collectible[]
     * @throws \Exception
     */
    public function validElements(array $elements): array
    {
        foreach($elements as $element)
        {
            if(get_class($element) != $this->type && !is_subclass_of($element, $this->type, true))
                throw new \Exception("[MVQN\Collections\Collection] The element type: '".get_class($element).
                    "' must match or extend '". $this->type."'!");
        }

        return $elements;
    }

    #endregion

}
