<?php

declare(strict_types=1);

namespace FilterBundle\Validator\Constraints;

use FilterBundle\Bridge\Doctrine\Common\DateFilterInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DateRangeBeforeNotEqualsAfterValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!($constraint instanceof DateRangeBeforeNotEqualsAfter)) {
            throw new UnexpectedTypeException($constraint, DateRangeBeforeNotEqualsAfter::class);
        }

        if (!is_array($value)) {
            return;
        }

        $equalNonStrictValues = !empty($value[DateFilterInterface::PARAMETER_AFTER])
            && !empty($value[DateFilterInterface::PARAMETER_BEFORE])
            && (strtotime($value[DateFilterInterface::PARAMETER_AFTER])
                == strtotime($value[DateFilterInterface::PARAMETER_BEFORE]));

        $equalStrictValues = !empty($value[DateFilterInterface::PARAMETER_STRICTLY_AFTER])
            && !empty($value[DateFilterInterface::PARAMETER_STRICTLY_BEFORE])
            && (strtotime($value[DateFilterInterface::PARAMETER_STRICTLY_AFTER])
                == strtotime($value[DateFilterInterface::PARAMETER_STRICTLY_BEFORE]));

        if ($equalNonStrictValues || $equalStrictValues) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
