<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * A Graph API rejection. It carries the Graph error code so a caller can tell a
 * refused token — which never reached the page, leaving the post safe to queue
 * again — from a rejection of this particular post.
 */
class FacebookPublishException extends RuntimeException
{
    /**
     * Graph codes that refuse the credentials rather than the post: 190 is an
     * invalid or expired token, 10 and 200 are missing page permissions. Meta
     * answers all three before writing anything, so no story exists.
     */
    private const CREDENTIAL_CODES = [10, 190, 200];

    public function __construct(string $message, public readonly int $graphCode = 0)
    {
        parent::__construct($message);
    }

    public static function rejected(Response $response): self
    {
        return new self(
            __('Facebook rejected the post: :error', ['error' => self::readError($response)]),
            (int) $response->json('error.code', 0),
        );
    }

    public function credentialsRejected(): bool
    {
        return in_array($this->graphCode, self::CREDENTIAL_CODES, true);
    }

    /**
     * Graph errors carry a readable message; fall back to the raw body when the
     * response is not the shape we expect.
     */
    public static function readError(Response $response): string
    {
        return (string) ($response->json('error.message') ?? $response->body());
    }
}
