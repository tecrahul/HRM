<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PurgeTrashedMessages extends Command
{
    protected $signature = 'communication:purge-trashed';

    protected $description = 'Permanently delete messages trashed by both parties over 30 days ago and cleanup attachments';

    public function handle(): int
    {
        $threshold = now()->subDays(30);

        $query = Message::query()
            ->whereNotNull('sender_trashed_at')
            ->whereNotNull('receiver_trashed_at')
            ->where('sender_trashed_at', '<=', $threshold)
            ->where('receiver_trashed_at', '<=', $threshold)
            ->orderBy('id')
            ->limit(1000);

        $total = 0;
        do {
            $batch = $query->get();
            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $message) {
                // Best-effort attachment cleanup if stored locally
                $attachments = (array) ($message->attachments ?? []);
                foreach ($attachments as $path) {
                    $pathStr = (string) $path;
                    // Our storeAttachments prefixes with 'storage/' publicly accessible path
                    if (str_starts_with($pathStr, 'storage/')) {
                        $diskPath = substr($pathStr, strlen('storage/'));
                        try {
                            Storage::disk('public')->delete($diskPath);
                        } catch (\Throwable $e) {
                            Log::warning('Failed deleting message attachment', [
                                'path' => $pathStr,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                $message->delete();
                $total++;
            }
        } while (true);

        $this->info("Purged {$total} trashed messages older than 30 days.");
        return Command::SUCCESS;
    }
}

