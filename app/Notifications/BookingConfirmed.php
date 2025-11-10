<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;
    
    protected $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        // Must load the ticket and event relationships here for the email content
        $this->booking = $booking->load('ticket.event');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Queue the mail delivery
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Your Event Booking Confirmation')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Your booking for **' . $this->booking->ticket->event->title . '** has been successfully confirmed!')
                    ->line('Booking ID: #' . $this->booking->id)
                    ->line('Total Paid: Ksh ' . number_format($this->booking->total_price, 2))
                    ->action('View Your Booking', url('/api/bookings/' . $this->booking->id))
                    ->line('Thank you for using our service!');
    }

    /**
     * Get the array representation of the notification. (For database storage, optional)
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'event_title' => $this->booking->ticket->event->title,
            'status' => 'confirmed',
        ];
    }
}