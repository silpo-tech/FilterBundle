<?php

declare(strict_types=1);

namespace FilterBundle\Validator\Constraints;

use DateTime;
use FilterBundle\Bridge\Doctrine\Common\DateFilterInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DateRangeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof DateRange) {
            throw new UnexpectedTypeException($constraint, DateRange::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        if (empty($value)) {
            return;
        }

        if (!$this->checkPayload($value, $constraint->format)) {
            $this->context->buildViolation($constraint->invalidDateTimeMessage)->addViolation();

            return;
        }

        $from = DateTime::createFromFormat($constraint->format, $value[DateFilterInterface::PARAMETER_AFTER]);
        $to = DateTime::createFromFormat($constraint->format, $value[DateFilterInterface::PARAMETER_BEFORE]);

        if ($from > $to) {
            $this->context->buildViolation($constraint->invalidDateRangeMessage)
                ->addViolation()
            ;

            return;
        }

        if (null !== $constraint->min) {
            $boundary = (clone $from)->modify($constraint->min);

            if ($boundary > $to) {
                $this->context->buildViolation($constraint->minMessage)
                    ->addViolation()
                ;

                return;
            }
        }

        if (null !== $constraint->max) {
            $boundary = (clone $from)->modify($constraint->max);

            if ($boundary < $to) {
                $this->context->buildViolation($constraint->maxMessage)
                    ->addViolation()
                ;
            }
        }
    }

    protected function checkPayload(array $value, string $format): bool
    {
        if (
            !empty(
                array_diff(
                    [DateFilterInterface::PARAMETER_AFTER, DateFilterInterface::PARAMETER_BEFORE],
                    array_keys(array_filter($value)),
                )
            )
        ) {
            return false;
        }

        foreach ($value as $timestamp) {
            if (!is_string($timestamp)) {
                return false;
            }

            $date = DateTime::createFromFormat($format, $timestamp);

            if (!$date || $date->format($format) !== $timestamp) {
                return false;
            }
        }

        return true;
    }
}
