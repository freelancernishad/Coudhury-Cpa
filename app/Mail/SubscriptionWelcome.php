<?php

namespace App\Mail;

use App\Models\BlogSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public $subscriber;
    public $unsubscribeUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(BlogSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
        $this->unsubscribeUrl = config('app.frontend_url', 'http://localhost:3001') . '/blog/unsubscribe?token=' . $subscriber->unsubscribe_token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Chaudri CPA Blog Newsletter!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.blogs.welcome',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
