<?php
namespace Anonymizer;

use Anonymizer\Exception\CircularReferenceException;
use Nette\Utils\Strings;

class Anonymizer
{
    /** @var string[] */
    private array $partialFields = [];

    /** @var string[] */
    private array $fullFields = [];

    /** @var string[] */
    private array $regexes = [];

    public const MASK = '********';

    public const REGEX_EMAIL = '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i';

    /**
     * @param string[] $fields
     */
    public function configPartialFields(array $fields): self
    {
        $this->partialFields = $fields;
        return $this;
    }

    /**
     * @param string[] $fields
     */
    public function configFullFields(array $fields): self
    {
        $this->fullFields = $fields;
        return $this;
    }

    public function configRegexMatch(string $regex): self
    {
        $this->regexes[] = $regex;
        return $this;
    }

    /**
     * @param array $data
     * @return array
     * @throws CircularReferenceException
     */
    public function anonymize(array $data): array
    {
        $visited = new \SplObjectStorage();
        $this->traverseArray($data, $visited);
        return $data;
    }

    private function traverseArray(array &$data, \SplObjectStorage $visited): void
    {
        $ref = new ArrayRef($data);
        if ($visited->contains($ref)) {
            throw new CircularReferenceException('Circular reference detected');
        }
        $visited->attach($ref);

        foreach ($data as $key => &$value) {
            $this->processItem($key, $value, $visited, $data);
        }
        $visited->detach($ref);
    }

    private function traverseObject(object $object, \SplObjectStorage $visited): void
    {
        if ($visited->contains($object)) {
            throw new CircularReferenceException('Circular reference detected');
        }
        $visited->attach($object);

        foreach ($object as $key => &$value) {
            $this->processItem($key, $value, $visited, $object);
        }
        $visited->detach($object);
    }

    private function processItem($key, &$value, \SplObjectStorage $visited, &$container): void
    {
        if (is_string($key)) {
            if (in_array($key, $this->fullFields, true)) {
                if (is_array($container)) {
                    $container[$key] = self::MASK;
                } else {
                    $container->$key = self::MASK;
                }
                return;
            }
            if (in_array($key, $this->partialFields, true) && is_string($value)) {
                $masked = $this->partialMask($value);
                if (is_array($container)) {
                    $container[$key] = $masked;
                } else {
                    $container->$key = $masked;
                }
                return;
            }
        }

        if (is_string($value)) {
            foreach ($this->regexes as $regex) {
                if (Strings::match($value, $regex)) {
                    if (is_array($container)) {
                        $container[$key] = self::MASK;
                    } else {
                        $container->$key = self::MASK;
                    }
                    return;
                }
            }
        } elseif (is_array($value)) {
            $this->traverseArray($value, $visited);
            if (is_array($container)) {
                $container[$key] = $value;
            } else {
                $container->$key = $value;
            }
        } elseif (is_object($value)) {
            $this->traverseObject($value, $visited);
            if (is_array($container)) {
                $container[$key] = $value;
            } else {
                $container->$key = $value;
            }
        }
    }

    private function partialMask(string $value): string
    {
        $len = Strings::length($value);
        if ($len <= 3) {
            return str_repeat('*', $len);
        }
        $start = Strings::substring($value, 0, 2);
        $end = Strings::substring($value, -1);
        return $start . str_repeat('*', $len - 3) . $end;
    }
}
