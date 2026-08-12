<?php

namespace App\Console\Commands;

use App\Services\FacebookPostPublisher;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Read-only diagnostic for the page credentials in `config('publishing.facebook')`.
 * Nothing is posted: it asks the Graph API who the token belongs to and how long
 * it lives, so a broken token is caught here rather than by a scheduled post
 * silently failing at its slot.
 */
class CheckFacebookPage extends Command
{
    protected $signature = 'facebook:check';

    protected $description = 'Verify the configured Facebook page id and access token against the Graph API';

    /** Permissions the publisher needs to post copy and photos to the page. */
    private const REQUIRED_SCOPES = ['pages_manage_posts', 'pages_read_engagement'];

    public function handle(): int
    {
        if (! FacebookPostPublisher::enabled()) {
            $this->components->error('FACEBOOK_PAGE_ID and FACEBOOK_PAGE_TOKEN are not both set.');

            return Command::FAILURE;
        }

        $identity = $this->get('me', ['fields' => 'id,name,link']);

        if ($identity->failed()) {
            $this->components->error('The token was rejected: '.$this->readError($identity));

            return Command::FAILURE;
        }

        $this->components->twoColumnDetail('Page', (string) $identity->json('name'));
        $this->components->twoColumnDetail('Page id', (string) $identity->json('id'));

        $configuredId = (string) config('publishing.facebook.page_id');
        $tokenPageId = (string) $identity->json('id');

        if ($tokenPageId !== $configuredId) {
            $this->newLine();
            $this->components->error("The token belongs to page {$tokenPageId}, but FACEBOOK_PAGE_ID is {$configuredId}.");
            $this->line('  A user token will do this too — derive the page token from <options=bold>/me/accounts</>.');

            return Command::FAILURE;
        }

        return $this->inspectToken();
    }

    /**
     * `debug_token` reveals the expiry and granted scopes. It needs a token issued
     * by the same app, which the page token itself satisfies for an app admin —
     * when Meta refuses, the identity check above still stands on its own.
     */
    private function inspectToken(): int
    {
        $debug = $this->get('debug_token', [
            'input_token' => $this->token(),
        ]);

        if ($debug->failed()) {
            $this->newLine();
            $this->components->warn('Could not read the token metadata, so its expiry and scopes are unverified.');
            $this->line('  '.$this->readError($debug));

            return Command::SUCCESS;
        }

        $expiresAt = (int) $debug->json('data.expires_at');
        $scopes = (array) $debug->json('data.scopes', []);
        $missing = array_diff(self::REQUIRED_SCOPES, $scopes);

        $this->components->twoColumnDetail(
            'Expires',
            $expiresAt === 0 ? '<fg=green>never</>' : '<fg=yellow>'.now()->createFromTimestamp($expiresAt)->toDayDateTimeString().'</>',
        );
        $this->components->twoColumnDetail('Scopes', $scopes === [] ? '—' : implode(', ', $scopes));

        $this->newLine();

        if ($missing !== []) {
            $this->components->error('Missing permission(s): '.implode(', ', $missing));
            $this->line('  Regenerate the token in the Graph API Explorer with those scopes ticked.');

            return Command::FAILURE;
        }

        if ($expiresAt !== 0) {
            $this->components->warn('This token expires, so scheduled posts will start failing on that date.');
            $this->line('  Exchange the user token for a long-lived one before reading <options=bold>/me/accounts</>.');

            return Command::FAILURE;
        }

        $this->components->info('The page is connected and the token does not expire.');

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, string>  $query
     */
    private function get(string $path, array $query): Response
    {
        $version = config('publishing.facebook.graph_version');

        return Http::timeout(30)->get(
            "https://graph.facebook.com/{$version}/{$path}",
            $query + ['access_token' => $this->token()],
        );
    }

    private function token(): string
    {
        return (string) config('publishing.facebook.access_token');
    }

    private function readError(Response $response): string
    {
        return (string) ($response->json('error.message') ?? $response->body());
    }
}
