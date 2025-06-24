<?php
namespace Anonymizer;

/**
 * Wrapper for array reference to allow circular reference detection.
 */
final class ArrayRef
{
    /** @var array */
    public $array;

    public function __construct(array &$array)
    {
        $this->array =& $array;
    }
}
