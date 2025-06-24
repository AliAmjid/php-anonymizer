<?php
namespace Anonymizer\Tests;

use Anonymizer\Anonymizer;
use Anonymizer\Exception\CircularReferenceException;
use PHPUnit\Framework\TestCase;

class AnonymizerTest extends TestCase
{
    public function testPartialAndFullAnonymization()
    {
        $anonymizer = (new Anonymizer())
            ->configPartialFields(['name'])
            ->configFullFields(['password']);

        $data = [
            'name' => 'Amjad',
            'password' => 'secret',
        ];

        $result = $anonymizer->anonymize($data);

        $this->assertSame('Am**d', $result['name']);
        $this->assertSame(Anonymizer::MASK, $result['password']);
    }

    public function testRegexAnonymization()
    {
        $anonymizer = (new Anonymizer())
            ->configRegexMatch(Anonymizer::REGEX_EMAIL);

        $data = [
            'email' => 'user@example.com',
        ];

        $result = $anonymizer->anonymize($data);

        $this->assertSame(Anonymizer::MASK, $result['email']);
    }

    public function testNestedArrayAnonymization()
    {
        $anonymizer = (new Anonymizer())
            ->configPartialFields(['name']);

        $data = [
            'level1' => [
                'level2' => [
                    'name' => 'Robert',
                ],
            ],
        ];

        $result = $anonymizer->anonymize($data);

        $this->assertSame('Ro***t', $result['level1']['level2']['name']);
    }

    public function testCircularReferenceDetection()
    {
        $anonymizer = new Anonymizer();
        $object = new \stdClass();
        $object->self = $object;
        $data = ['obj' => $object];

        $this->expectException(CircularReferenceException::class);
        $anonymizer->anonymize($data);
    }
}
