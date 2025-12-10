<?php

namespace App\Http\Controllers;

/**
 * @OA\Info( *      version="1.0.0", *      title="Exchange Mini API", *      description="API documentation for the Exchange Mini project. Provides endpoints for user authentication, profile management, and order/trade operations.", *      @OA\Contact( *          email="support@exchange-mini.com" *      ) * ) * @OA\SecurityScheme( *      securityScheme="bearerAuth", *      type="http", *      scheme="bearer" * )
 * @OA\Schema(
 *     schema="ValidationError",
 *     title="Validation Error",
 *     description="Standard validation error response",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="The given data was invalid."
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         example={"email": {"The email field is required."}}
 *     )
 * )
 */
abstract class Controller
{
    //
}
