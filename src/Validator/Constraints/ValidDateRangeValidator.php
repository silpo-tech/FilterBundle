<?php

declare(strict_types=1);

namespace FilterBundle\Validator\Constraints;

use DateTime;
use FilterBundle\Bridge\Doctrine\Orm\DateFilter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidDateRangeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!($constraint instanceof ValidDateRange)) {
            throw new UnexpectedTypeException($constraint, ValidDateRange::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $notValid = false;

        foreach ($value as $paramName => $val) {
            if (
                !in_array($paramName, DateFilter::POSSIBLE_PARAMETERS)
                || !$this->isValidValue($val, $constraint->format)
            ) {
                $notValid = true;
                break;
            }
        }

        if ($notValid) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    private function isValidValue($value, string $format): bool
    {
        if (($value === null) || !is_string($value)) {
            return false;
        }

        $date = DateTime::createFromFormat($format, $value);

        return $date && $date->format($format) === $value;
    }
}
