<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\BlogSubscriber;
use App\Mail\NewBlogNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNewBlogNotificationJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $article;

    /**
     * Create a new job instance.
     */
    public function __construct(Article $article)
    {
        $this->article = $article;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $subscribers = BlogSubscriber::where('is_active', true)->get();

        foreach ($subscribers as $subscriber) {
            Mail::to($subscriber->email)->send(new NewBlogNotification($this->article, $subscriber));
        }
    }
}
