<?php

declare(strict_types=1);

namespace FilterBundle\Validator\Constraints;

use FilterBundle\Bridge\Doctrine\Common\DateFilterInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DateRangeBeforeGreaterThanAfterValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!($constraint instanceof DateRangeBeforeGreaterThanAfter)) {
            throw new UnexpectedTypeException($constraint, DateRangeBeforeGreaterThanAfter::class);
        }

        if (!is_array($value)) {
            return;
        }

        $notValidNonStrictRange = !empty($value[DateFilterInterface::PARAMETER_AFTER])
            && !empty($value[DateFilterInterface::PARAMETER_BEFORE])
            && (strtotime($value[DateFilterInterface::PARAMETER_AFTER])
                > strtotime($value[DateFilterInterface::PARAMETER_BEFORE]));

        $notValidStrictRange = !empty($value[DateFilterInterface::PARAMETER_STRICTLY_AFTER])
            && !empty($value[DateFilterInterface::PARAMETER_STRICTLY_BEFORE])
            && (strtotime($value[DateFilterInterface::PARAMETER_STRICTLY_AFTER])
                > strtotime($value[DateFilterInterface::PARAMETER_STRICTLY_BEFORE]));

        if ($notValidNonStrictRange || $notValidStrictRange) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
