<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\Business;
use App\Mail\CommonTemplateMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Exception;

class EmailTemplateService
{
    private static $sentEmails = [];
    public function sendTemplateEmail(string $templateName, array $variables, string $toEmail, Business $business = null, string $toName = null)
    {
        // Skip email sending in demo mode
        if (config('app.is_demo', true)) {
            \Log::info('Email sending skipped - demo mode enabled');
            return true;
        }

        // Prevent duplicate emails within same request
        $emailKey = md5($templateName . $toEmail . serialize($variables));
        if (isset(self::$sentEmails[$emailKey])) {
            \Log::info('Duplicate email prevented', ['template' => $templateName, 'email' => $toEmail]);
            return true;
        }
        self::$sentEmails[$emailKey] = true;

        try {
            // Get email template
            $template = EmailTemplate::where('name', $templateName)->first();

            if (!$template) {
                throw new Exception("Email template '{$templateName}' not found");
            }

            // Get user's language or default to 'en'
            $language = 'en'; // default
            if ($business && $business->user) {
                $language = $business->user->lang ?? 'en';
            }

            // Get template content for the language
            $templateLang = $template->emailTemplateLangs()
                ->where('lang', $language)
                ->first();

            // Fallback to English if language not found
            if (!$templateLang) {
                $templateLang = $template->emailTemplateLangs()
                    ->where('lang', 'en')
                    ->first();
            }

            if (!$templateLang) {
                throw new Exception("No content found for template '{$templateName}'");
            }

            // Replace variables in subject and content
            $subject = $this->replaceVariables($templateLang->subject, $variables);
            $content = $this->replaceVariables($templateLang->content, $variables);
            $fromName = $this->replaceVariables($template->from, $variables);

            // Configure SMTP settings
            $this->configureBusinessSMTP();

            // Get final email settings
            $fromEmail = getSetting('email_from_address') ?: config('mail.from.address');
            $finalFromName = getSetting('email_from_name') ? $this->replaceVariables(getSetting('email_from_name'), $variables) : $fromName;

            // Send email using Mail class
            $mail = new CommonTemplateMail($subject, $content, $variables, $language);
            $mail->from($fromEmail, $finalFromName);
            Mail::to($toEmail, $toName)->send($mail);

            return true;
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            \Log::error('Email sending failed: ' . $errorMessage);

            // Handle rate limiting gracefully
            if (str_contains($errorMessage, 'Too many emails per second')) {
                \Log::warning('Email rate limit exceeded');
                return false;
            }

            // Handle SMTP authentication errors
            if (str_contains($errorMessage, 'Authentication failed') || str_contains($errorMessage, 'Invalid credentials')) {
                \Log::error('SMTP Authentication failed - check email credentials');
                return false;
            }

            // Handle connection errors
            if (str_contains($errorMessage, 'Connection refused') || str_contains($errorMessage, 'Connection timed out')) {
                \Log::error('SMTP Connection failed - check host and port');
                return false;
            }

            // For other errors, return false to prevent retries
            return false;
        }
    }

    private function replaceVariables(?string $content, array $variables): string
    {
        if ($content === null) {
            return '';
        }
        return str_replace(array_keys($variables), array_values($variables), $content);
    }

    public function sendTemplateEmailWithLanguage(string $templateName, array $variables, string $toEmail, string $toName = null, string $language = 'en')
    {
        // Skip email sending in demo mode
        if (config('app.is_demo', false)) {
            \Log::info('Email sending skipped - demo mode enabled');
            return true;
        }

        // Prevent duplicate emails within same request
        $emailKey = md5($templateName . $toEmail . serialize($variables));
        if (isset(self::$sentEmails[$emailKey])) {
            \Log::info('Duplicate email prevented', ['template' => $templateName, 'email' => $toEmail]);
            return true;
        }
        self::$sentEmails[$emailKey] = true;

        try {
            \Log::info('=== EMAIL TEMPLATE LANGUAGE DEBUG ===', [
                'template_name' => $templateName,
                'requested_language' => $language,
                'to_email' => $toEmail
            ]);

            // Get email template
            $template = EmailTemplate::where('name', $templateName)->first();

            if (!$template) {
                throw new Exception("Email template '{$templateName}' not found");
            }

            // Get template content for the specified language
            $templateLang = $template->emailTemplateLangs()
                ->where('lang', $language)
                ->first();

            \Log::info('Template language lookup', [
                'requested_lang' => $language,
                'found_template' => $templateLang ? true : false,
                'template_id' => $templateLang?->id ?? null
            ]);

            // Log template content before replacement
            \Log::info('Template content before replacement:', [
                'subject' => $templateLang->subject ?? 'N/A',
                'content_preview' => $templateLang ? substr($templateLang->content, 0, 200) . '...' : 'N/A'
            ]);

            \Log::info('Variables for replacement:', $variables);


            // Fallback to English if language not found
            if (!$templateLang) {
                $templateLang = $template->emailTemplateLangs()
                    ->where('lang', 'en')
                    ->first();
            }

            if (!$templateLang) {
                throw new Exception("No content found for template '{$templateName}'");
            }

            // Replace variables in subject and content
            $subject = $this->replaceVariables($templateLang->subject, $variables);
            $content = $this->replaceVariables($templateLang->content, $variables);
            $fromName = $this->replaceVariables($template->from, $variables);

            // Configure SMTP settings
            $this->configureBusinessSMTP();

            // Get final email settings
            $fromEmail = getSetting('email_from_address') ?: config('mail.from.address');
            $finalFromName = getSetting('email_from_name') ? $this->replaceVariables(getSetting('email_from_name'), $variables) : $fromName;

            // Send email using Mail class
            $mail = new CommonTemplateMail($subject, $content, $variables, $language);
            $mail->from($fromEmail, $finalFromName);
            Mail::to($toEmail, $toName)->send($mail);

            return true;
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            \Log::error('Email sending failed: ' . $errorMessage);

            // Handle rate limiting gracefully
            if (str_contains($errorMessage, 'Too many emails per second')) {
                \Log::warning('Email rate limit exceeded, email will be retried later');
                return false;
            }

            // Handle SMTP authentication errors
            if (str_contains($errorMessage, 'Authentication failed') || str_contains($errorMessage, 'Invalid credentials')) {
                \Log::error('SMTP Authentication failed - check email credentials');
                session()->flash('email_error', 'SMTP Authentication failed. Please check your email credentials.');
                return false;
            }

            // Handle connection errors
            if (str_contains($errorMessage, 'Connection refused') || str_contains($errorMessage, 'Connection timed out')) {
                \Log::error('SMTP Connection failed - check host and port');
                session()->flash('email_error', 'SMTP Connection failed. Please check your email host and port settings.');
                return false;
            }

            // Store error in session for display
            session()->flash('email_error', $errorMessage);
            throw $e;
        }
    }

    private function configureBusinessSMTP(?Business $business = null)
    {
        // Get email settings from settings table
        $emailDriver = getSetting('email_driver', 'smtp');
        $emailHost = getSetting('email_host');
        $emailUsername = getSetting('email_username');
        $emailPassword = getSetting('email_password');
        $emailPort = getSetting('email_port', 587);
        $emailEncryption = getSetting('email_encryption', 'tls');

        // Check if email settings are configured
        if (!$emailHost) {
            throw new Exception("Email host not configured. Please set SMTP host in email settings.");
        }
        if (!$emailUsername) {
            throw new Exception("Email username not configured. Please set SMTP username in email settings.");
        }
        if (!$emailPassword) {
            throw new Exception("Email password not configured. Please set SMTP password in email settings.");
        }

        // Configure mail settings
        Config::set([
            'mail.default' => $emailDriver,
            'mail.mailers.smtp.host' => $emailHost,
            'mail.mailers.smtp.port' => $emailPort,
            'mail.mailers.smtp.username' => $emailUsername,
            'mail.mailers.smtp.password' => $emailPassword,
            'mail.mailers.smtp.encryption' => $emailEncryption,
        ]);
    }
}
