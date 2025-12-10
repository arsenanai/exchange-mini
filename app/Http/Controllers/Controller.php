<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Exchange Mini API',
    description: 'API documentation for the Exchange Mini project...',
    contact: new OA\Contact(email: 'support@exchange-mini.com')
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
)]
abstract class Controller
{
    //
}
