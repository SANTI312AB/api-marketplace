<?php

namespace App\Interfaces;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class ErrorsInterface{

      public function form_errors(FormInterface $form):JsonResponse{
 
        $errors = [];
        foreach ($form->getErrors(true, true) as $error) {
            $propertyName = $error->getOrigin()->getName();
            $errors[$propertyName][] = $error->getMessage();
        }
 
        return new JsonResponse([
            'message' => 'Algunos campos no son válidos, por favor verifique los datos e intente nuevamente.',
            'errors' => $errors,
        ],400);   

      }


      public function form_error(FormInterface $form,$mensajeError,$objet=null):JsonResponse{

        if(!$objet){
            $objet='description';
        }
        $errors = []; // Initialize the errors array
        foreach ($form->getErrors(true, true) as $formError) { // Renamed variable to $formError
            $errors[] = [
                $objet => $formError->getMessage(),
            ];
            break; // Capture only the first error
        }
        
        return new JsonResponse([
            'message' => $mensajeError,
            'errors' => $errors
        ],400); 

      }


      public function form_string_error( FormInterface $form,$object = null, $data = null):JsonResponse{

        $errorData = null;
      
        if ($object !== null && $data !== null) {
            // Si se proporciona un objeto, anidar $data bajo esa clave
            $errorData = [$object => $data];
        } elseif ($data !== null) {
            // Si no hay objeto, usar $data directamente
            $errorData = $data;
        }

        foreach ($form->getErrors(true, true) as $error) {
            $errorString = $error->getMessage();
            break; // Salir del bucle después de encontrar el primer error
        }
        
        return new JsonResponse([
            'message' => $errorString,
            'errors' => $errorData
        ],400);
      }

      public function succes_message($mensaje, $object = null, $data = null): JsonResponse
      {
          $responseData = null;
      
          if ($object !== null && $data !== null) {
              // Si se proporciona un objeto, anidar $data bajo esa clave
              $responseData = [$object => $data];
          } elseif ($data !== null) {
              // Si no hay objeto, usar $data directamente (puede ser un array con múltiples claves)
              $responseData = $data;
          }
      
          return new JsonResponse([
              'message' => $mensaje,
              'data' => $responseData
          ], 200);
      }

      public function error_message($mensaje, $status, $object = null, $data = null): JsonResponse
      {
          $errorData = null;
      
          if ($object !== null && $data !== null) {
              // Si se proporciona un objeto, anidar $data bajo esa clave
              $errorData = [$object => $data];
          } elseif ($data !== null) {
              // Si no hay objeto, usar $data directamente
              $errorData = $data;
          }
      
          return new JsonResponse([
              'message' => $mensaje,
              'errors' => $errorData
          ], $status);
      }

}