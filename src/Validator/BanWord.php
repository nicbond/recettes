<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class BanWord extends Constraint
{
    /**
     * @param array<int, string> $banWords
     */
    public function __construct(
        public string $message = 'This contains a banned word "{{ banWord }}".',
        public array $banWords = ['spam', 'viagra', 'sexy', 'hot', 'interdit', 'trash', 'bourré', 'ivre', 'dégueu', 'pourri'],
        public string $mode = 'strict',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }
}
