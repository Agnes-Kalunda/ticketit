<?php

namespace Ticket\Ticketit\Controllers;

use Illuminate\Support\Facades\Mail;
use Ticket\Ticketit\Helpers\LaravelVersion;
use Ticket\Ticketit\Models\Comment;
use Ticket\Ticketit\Models\Setting;
use Ticket\Ticketit\Models\Ticket;

class NotificationsController extends BaseTicketController
{
    public function newComment(Comment $comment)
    {
        $ticket = $comment->ticket;
        
        // Handle notification owner based on auth type
        if ($comment->customer_id) {
            $notification_owner = $comment->customer;
        } else {
            $notification_owner = $comment->user;
        }

        $template = 'ticketit::emails.comment';
        $data = [
            'comment' => serialize($comment), 
            'ticket' => serialize($ticket)
        ];

        $this->sendNotification(
            $template, 
            $data, 
            $ticket, 
            $notification_owner,
            trans('ticketit::lang.notify-new-comment-from') . 
            $notification_owner->name . 
            trans('ticketit::lang.notify-on') . 
            $ticket->subject, 
            'comment'
        );
    }

    public function ticketStatusUpdated(Ticket $ticket, Ticket $original_ticket)
    {
        $notification_owner = $this->getAuthUser();
        $template = 'ticketit::emails.status';
        $data = [
            'ticket'             => serialize($ticket),
            'notification_owner' => serialize($notification_owner),
            'original_ticket'    => serialize($original_ticket),
        ];

        if (strtotime($ticket->completed_at)) {
            $this->sendNotification(
                $template, 
                $data, 
                $ticket, 
                $notification_owner,
                $notification_owner->name . 
                trans('ticketit::lang.notify-updated') . 
                $ticket->subject . 
                trans('ticketit::lang.notify-status-to-complete'), 
                'status'
            );
        } else {
            $this->sendNotification(
                $template, 
                $data, 
                $ticket, 
                $notification_owner,
                $notification_owner->name . 
                trans('ticketit::lang.notify-updated') . 
                $ticket->subject . 
                trans('ticketit::lang.notify-status-to') . 
                $ticket->status->name, 
                'status'
            );
        }
    }

    public function ticketAgentUpdated(Ticket $ticket, Ticket $original_ticket)
    {
        $notification_owner = $this->getAuthUser();
        $template = 'ticketit::emails.transfer';
        $data = [
            'ticket'             => serialize($ticket),
            'notification_owner' => serialize($notification_owner),
            'original_ticket'    => serialize($original_ticket),
        ];

        $this->sendNotification(
            $template, 
            $data, 
            $ticket, 
            $notification_owner,
            $notification_owner->name . 
            trans('ticketit::lang.notify-transferred') . 
            $ticket->subject . 
            trans('ticketit::lang.notify-to-you'), 
            'agent'
        );
    }

    public function newTicketNotifyAgent(Ticket $ticket)
    {
        $notification_owner = $this->getAuthUser();
        $template = 'ticketit::emails.assigned';
        $data = [
            'ticket'             => serialize($ticket),
            'notification_owner' => serialize($notification_owner),
        ];

        $this->sendNotification(
            $template, 
            $data, 
            $ticket, 
            $notification_owner,
            $notification_owner->name . 
            trans('ticketit::lang.notify-created-ticket') . 
            $ticket->subject, 
            'agent'
        );
    }

    /**
     * Send email notifications from the action owner to other involved users.
     */
    public function sendNotification($template, $data, $ticket, $notification_owner, $subject, $type)
    {
        $to = null;

        if ($type != 'agent') {
            // Handle customer or user recipient
            if ($ticket->customer_id) {
                $to = $ticket->customer;
                if ($ticket->customer->email == $notification_owner->email) {
                    $to = $ticket->agent;
                }
            } else {
                $to = $ticket->user;
                if ($ticket->user->email == $notification_owner->email) {
                    $to = $ticket->agent;
                }
            }

            if ($ticket->agent->email == $notification_owner->email) {
                $to = $ticket->customer_id ? $ticket->customer : $ticket->user;
            }
        } else {
            $to = $ticket->agent;
        }

        if (LaravelVersion::lt('5.4')) {
            $mail_callback = function ($m) use ($to, $notification_owner, $subject) {
                $m->to($to->email, $to->name);
                $m->replyTo($notification_owner->email, $notification_owner->name);
                $m->subject($subject);
            };

            if (Setting::grab('queue_emails') == 'yes') {
                Mail::queue($template, $data, $mail_callback);
            } else {
                Mail::send($template, $data, $mail_callback);
            }
        } elseif (LaravelVersion::min('5.4')) {
            $mail = new \Ticket\Ticketit\Mail\TicketitNotification(
                $template, 
                $data, 
                $notification_owner, 
                $subject
            );

            if (Setting::grab('queue_emails') == 'yes') {
                Mail::to($to)->queue($mail);
            } else {
                Mail::to($to)->send($mail);
            }
        }
    }
}