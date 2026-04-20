<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CedulaEcuatoriana extends Constraint
{
    public $message = 'El número de cédula  "{{ value }}" no es válido.';
}