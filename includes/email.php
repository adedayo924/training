<?php
/**
 * Email Functions
 *
 * Sends registration confirmation emails using PHP mail().
 */

/**
 * Send a registration confirmation email.
 *
 * @param string $email    Recipient email
 * @param string $name     Recipient name
 * @param array  $event    Event data (title, date, time, type, venue, meeting_link)
 * @return bool
 */
function sendRegistrationEmail(string $email, string $name, array $event): bool
{
    $siteName = APP_NAME;
    $subject  = "Registration Confirmed - {$event['title']}";

    $dateFormatted = date('l, F d, Y', strtotime($event['date']));
    $timeFormatted = date('h:i A', strtotime($event['time']));

    $location = '';
    if ($event['type'] === 'online' || $event['type'] === 'hybrid') {
        $location .= "Online";
        if (!empty($event['meeting_link'])) {
            $location .= " - <a href=\"{$event['meeting_link']}\">Click here to join</a>";
        }
    }
    if ($event['type'] === 'in-person' || $event['type'] === 'hybrid') {
        if (!empty($event['venue'])) {
            $location .= ($location ? '<br>' : '') . "Venue: {$event['venue']}";
        }
    }

    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f9; padding: 30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #198754; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">{$siteName}</h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #198754; margin-top: 0;">Registration Confirmed!</h2>
                            <p style="color: #333; font-size: 16px;">Hello {$name},</p>
                            <p style="color: #333; font-size: 16px;">Your registration for the following event has been confirmed:</p>

                            <table width="100%" cellpadding="12" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin: 20px 0; border-left: 4px solid #198754;">
                                <tr>
                                    <td>
                                        <strong style="font-size: 18px; color: #198754;">{$event['title']}</strong><br><br>
                                        <strong>Date:</strong> {$dateFormatted}<br>
                                        <strong>Time:</strong> {$timeFormatted}<br>
                                        <strong>Type:</strong> {$event['type']}<br>
                                        <strong>Location:</strong> {$location}
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #666; font-size: 14px;">Please save this email for your records. If you have any questions, please contact us.</p>

                            <p style="color: #666; font-size: 14px;">We look forward to seeing you!</p>

                            <p style="color: #333; font-size: 14px;">
                                Best regards,<br>
                                <strong>{$siteName} Team</strong>
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center;">
                            <p style="color: #999; font-size: 12px; margin: 0;">
                                &copy; {$dateFormatted} {$siteName}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    $from    = MAIL_FROM;
    $fromName = MAIL_FROM_NAME;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $result = @mail($email, $subject, $body, $headers);

    if (!$result) {
        logError("Failed to send email to {$email} for event: {$event['title']}");
    }

    return $result;
}
