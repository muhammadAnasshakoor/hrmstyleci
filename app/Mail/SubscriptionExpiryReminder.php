<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryReminder extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Expiry Reminder',
        );
    }

    /**
     * Get the message content definition.
     */
    public function build()
    {
        return $this->subject('Subscription Expiry Reminder')
            ->view('emails.SubscriptionExpiryReminder')
            ->from('info@logicalcreations.net', 'Logical Creations')
            ->with('data', $this->data); // Pass data to the view using the with method
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
