<?php
/**
 * WhatsApp Link Generator
 *
 * Generates wa.me links for sharing event registration info.
 */

/**
 * Generate a WhatsApp share link with pre-filled message.
 *
 * @param string $eventName  Name of the event
 * @param string $eventDate  Event date (formatted)
 * @param string $eventTime  Event time (formatted)
 * @param string $eventType  Event type
 *
 * @return string WhatsApp share URL
 */
function generateWhatsAppLink(string $eventName, string $eventDate, string $eventTime, string $eventType = ''): string
{
    $siteName = APP_NAME;

    $message = "Hello! I just registered for {$eventName} on {$eventDate} at {$eventTime}";
    if ($eventType) {
        $message .= " ({$eventType})";
    }
    $message .= " on {$siteName}. Looking forward to it!";

    return 'https://wa.me/?text=' . urlencode($message);
}

/**
 * Generate a WhatsApp direct message link (to a specific number).
 *
 * @param string $phone      Phone number (with country code)
 * @param string $eventName  Name of the event
 * @param string $name       Registrant name
 *
 * @return string WhatsApp direct link
 */
function generateWhatsAppDirectLink(string $phone, string $eventName, string $name = ''): string
{
    $siteName = APP_NAME;

    // Clean phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);

    $message = "Hello! {$name} has just registered for {$eventName} on {$siteName}.";
    if (empty($name)) {
        $message = "Hello! A new registration was just completed for {$eventName} on {$siteName}.";
    }

    return "https://wa.me/{$phone}?text=" . urlencode($message);
}
