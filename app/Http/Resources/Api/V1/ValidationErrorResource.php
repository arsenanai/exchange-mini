<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

/**
 * @OA\Schema(
 *     schema="ValidationError",
 *     title="Validation Error",
 *     description="Standard validation error response",
 *
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
class ValidationErrorResource {}
