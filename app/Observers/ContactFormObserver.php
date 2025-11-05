<?php

namespace App\Observers;

use App\Models\ContactForm;
use App\Models\Notification;

class ContactFormObserver
{
    /**
     * Handle the ContactForm "created" event.
     */
    public function created(ContactForm $contactForm): void
    {
        Notification::createContactFormNotification($contactForm);
    }
}
