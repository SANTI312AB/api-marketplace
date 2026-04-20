<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class RucEcuadorValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof RucEcuador) {
            throw new UnexpectedTypeException($constraint, RucEcuador::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // El RUC debe tener 13 dígitos numéricos
        if (!preg_match('/^\d{13}$/', $value)) {
            $this->addViolation($constraint, $value);
            return;
        }

        // Validar el código de provincia (dos primeros dígitos)
        $provincia = (int) substr($value, 0, 2);
        if ($provincia < 1 || $provincia > 22) {
            $this->addViolation($constraint, $value);
            return;
        }

        // Validar el tercer dígito:
        // Para personas naturales se permite de 0 a 5,
        // 6 para Instituciones públicas y 9 para Sociedades privadas.
        $tercerDigito = (int) $value[2];
        if (!in_array($tercerDigito, [0,1,2,3,4,5,6,9], true)) {
            $this->addViolation($constraint, $value);
            return;
        }

        // Según el tercer dígito se determina el tipo de contribuyente:
        if ($tercerDigito >= 0 && $tercerDigito <= 5) {
            $valido = $this->validarPersonaNatural($value);
        } elseif ($tercerDigito === 6) {
            $valido = $this->validarInstitucionPublica($value);
        } elseif ($tercerDigito === 9) {
            $valido = $this->validarSociedadPrivada($value);
        } else {
            $valido = false;
        }

        if (!$valido) {
            $this->addViolation($constraint, $value);
        }
    }

    private function addViolation(Constraint $constraint, string $ruc): void
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $ruc)
            ->addViolation();
    }

    /**
     * Valida RUC para personas naturales utilizando la cédula (primeros 10 dígitos)
     * y verifica que el establecimiento (últimos 3) no sea '000'.
     */
    private function validarPersonaNatural(string $ruc): bool
    {
        $cedula = substr($ruc, 0, 10);
        if (!$this->validarCedula($cedula)) {
            return false;
        }
        // Los 3 últimos dígitos (establecimiento) no pueden ser '000'
        return substr($ruc, -3) !== '000';
    }

    /**
     * Validación de cédula (algoritmo Módulo 10)
     */
    private function validarCedula(string $cedula): bool
    {
        if (strlen($cedula) !== 10) {
            return false;
        }

        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $multiplo = $i % 2 === 0 ? 2 : 1;
            $valor = (int) $cedula[$i] * $multiplo;
            $suma += ($valor >= 10) ? $valor - 9 : $valor;
        }

        $digitoVerificador = (10 - ($suma % 10)) % 10;
        return (int) $cedula[9] === $digitoVerificador;
    }

    /**
     * Valida RUC para sociedades privadas (tercer dígito 9) mediante algoritmo Módulo 11
     * y verifica que el establecimiento (últimos 3) no sea '000'.
     */
    private function validarSociedadPrivada(string $ruc): bool
    {
        if (!$this->validarDigitoVerificador($ruc, [4, 3, 2, 7, 6, 5, 4, 3, 2], 9)) {
            return false;
        }
        // Los 3 últimos dígitos (establecimiento) no pueden ser '000'
        return substr($ruc, -3) !== '000';
    }

    /**
     * Valida RUC para instituciones públicas (tercer dígito 6) mediante algoritmo Módulo 11
     * y verifica que el establecimiento (últimos 4) no sea '0000'.
     */
    private function validarInstitucionPublica(string $ruc): bool
    {
        if (!$this->validarDigitoVerificador($ruc, [3, 2, 7, 6, 5, 4, 3, 2, 1], 8)) {
            return false;
        }
        // Los últimos 4 dígitos (establecimiento) no pueden ser '0000'
        return substr($ruc, -4) !== '0000';
    }

    /**
     * Valida el dígito verificador usando el algoritmo Módulo 11.
     *
     * @param string $ruc Número completo de RUC
     * @param array $coeficientes Coeficientes según el tipo de contribuyente
     * @param int $posicionVerificador Posición del dígito verificador en el RUC
     * @return bool
     */
    private function validarDigitoVerificador(string $ruc, array $coeficientes, int $posicionVerificador): bool
    {
        $suma = 0;
        foreach ($coeficientes as $i => $coef) {
            $suma += (int) $ruc[$i] * $coef;
        }
        $resto = $suma % 11;
        $digitoEsperado = $resto === 0 ? 0 : (11 - $resto);
        if ($digitoEsperado === 10) {
            $digitoEsperado = 0;
        }
        return (int) $ruc[$posicionVerificador] === $digitoEsperado;
    }
}
