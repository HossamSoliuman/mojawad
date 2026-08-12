<?php

namespace App\Console\Commands;

use App\Services\FacebookPostPublisher;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Publishes one throwaway message through the same publisher the scheduler uses,
 * turning a permissions problem into a readable Graph error instead of a post
 * that quietly fails at its slot. Writes to the real page, so it confirms first.
 */
class PostTestFacebookMessage extends Command
{
    protected $signature = 'facebook:test-post
        {message? : Copy to publish; defaults to a timestamped test line}
        {--keep : Leave the post on the page instead of deleting it}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Publish a throwaway post to the Facebook page to prove the credentials work';

    public function handle(FacebookPostPublisher $publisher): int
    {
        if (! FacebookPostPublisher::enabled()) {
            $this->components->error('FACEBOOK_PAGE_ID and FACEBOOK_PAGE_TOKEN are not both set.');

            return Command::FAILURE;
        }

        $message = (string) ($this->argument('message') ?? 'Mojawad publishing test — '.now()->toDateTimeString());
        $keep = (bool) $this->option('keep');

        $this->components->twoColumnDetail('Page id', (string) config('publishing.facebook.page_id'));
        $this->components->twoColumnDetail('Message', $message);
        $this->components->twoColumnDetail('Afterwards', $keep ? '<fg=yellow>kept on the page</>' : 'deleted automatically');
        $this->newLine();

        if (! $this->option('force') && ! $this->components->confirm('Publish this to the live page now?', false)) {
            $this->components->warn('Nothing was published.');

            return Command::SUCCESS;
        }

        try {
            $result = $publisher->publishMessage($message);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());
            $this->hint($exception->getMessage());

            return Command::FAILURE;
        }

        $this->components->info('Published: '.$result['url']);

        return $keep ? Command::SUCCESS : $this->cleanUp($publisher, $result['id']);
    }

    private function cleanUp(FacebookPostPublisher $publisher, string $id): int
    {
        try {
            $publisher->deletePost($id);
        } catch (RuntimeException $exception) {
            $this->components->warn('The post is live but could not be removed: '.$exception->getMessage());
            $this->line('  Delete it from the page by hand.');

            return Command::FAILURE;
        }

        $this->components->info('The test post was deleted again.');

        return Command::SUCCESS;
    }

    /**
     * A permissions rejection is the expected failure while the app is still
     * being set up, so name the fix rather than leaving the raw code.
     */
    private function hint(string $error): void
    {
        if (! str_contains($error, 'ermission')) {
            return;
        }

        $this->line('  The token is missing <options=bold>pages_manage_posts</>.');
        $this->line('  Add it under App Dashboard → Use cases → Customize → Permissions, then mint a new token.');
    }
}
