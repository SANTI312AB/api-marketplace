<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CedulaEcuatorianaValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$this->isValidCedula($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }

    private function isValidCedula($value): bool
    {
        $cedula = preg_replace('/[^0-9]/', '', $value);

        if (strlen($cedula) !== 10) {
            return false;
        }

        $provinceDigits = (int) substr($cedula, 0, 2);
        $thirdDigit = (int) $cedula[2];
        $checkDigit = (int) $cedula[9];

        if ($provinceDigits < 0 || $provinceDigits > 24 || $thirdDigit >= 6) {
            return false;
        }

        $sum = 0;
        $coefficients = [2, 1, 2, 1, 2, 1, 2, 1, 2];

        for ($i = 0; $i < 9; $i++) {
            $digit = (int) $cedula[$i];
            $product = $digit * $coefficients[$i];
            $sum += ($product > 9) ? $product - 9 : $product;
        }

        $totalSum = $sum % 10;

        return ($totalSum === 0 && $checkDigit === 0) || $totalSum === 10 - $checkDigit;
    }
}
