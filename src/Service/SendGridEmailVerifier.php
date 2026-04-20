<?php

namespace App\Service;

use SendGrid;
use SendGrid\Mail\Mail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SendGridEmailVerifier
{
    private $sendGridApiKey;

    public function __construct(ParameterBagInterface $params)
    {
        $this->sendGridApiKey = $params->get('sengrid_apy_key');
    }

    public function verifyEmail(string $email): bool
    {
        $sendgrid = new SendGrid($this->sendGridApiKey);
        $requestBody = json_encode(['email' => $email]);

        try {
            $response = $sendgrid->client->email()->validate()->post([
                'body' => $requestBody,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $result = json_decode($response->body(), true);
            
            // Ajusta el siguiente código basado en la respuesta de la API
            if (isset($result['result']['verdict']) && $result['result']['verdict'] === 'Valid') {
                return true;
            } elseif (isset($result['result']['verdict']) && $result['result']['verdict'] === 'Invalid') {
                return false;
            }
        } catch (\Exception $e) {
            // Log the exception or handle the error as needed
            return false;
        }

        return false;
        
    }
}