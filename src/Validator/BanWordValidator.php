<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class BanWordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BanWord) {
            return;
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            return;
        }

        $value = strtolower($value);
        foreach ($constraint->banWords as $banWord) {
            if (str_contains($value, $banWord)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ banWord }}', $banWord)
                    ->addViolation()
                ;
            }
        }
    }
}
