<?php  

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class RucEcuador extends Constraint
{
    public $message = 'El RUC "{{ value }}" no es válido.';
}