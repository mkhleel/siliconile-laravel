<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Tenchology');
        $this->migrator->add('general.site_description', 'A modern and powerful Travel and Tour Management System built with love from Egypt, Luxor.');
        $this->migrator->add('general.site_languages', ['en']);
        $this->migrator->add('general.site_locale', 'en');
        $this->migrator->add('general.site_color', '');
        $this->migrator->add('general.site_logo', '');
        $this->migrator->add('general.site_dark_logo', '');
        $this->migrator->add('general.site_favicon', '');
        $this->migrator->add('general.site_theme', 'classic');
        $this->migrator->add('general.slider', []);
        $this->migrator->add('general.currency', 'USD');
        $this->migrator->add('general.preferred_currency', 'USD');
        $this->migrator->add('general.admin_prefix', 'admin');
        $this->migrator->add('general.site_active', true);
        $this->migrator->add('general.disabled_modules', []);

        $this->migrator->add('general.brands', []);
        $this->migrator->add('general.faqs', []);
        $this->migrator->add('general.testimonials', []);

        $this->migrator->add('general.ai_provider', '');
        $this->migrator->add('general.ai_model', '');
        $this->migrator->addEncrypted('general.ai_api_key', '');
        $this->migrator->add('general.site_social', []);

        $this->migrator->addEncrypted('general.zoom_client_id', '');
        $this->migrator->addEncrypted('general.zoom_client_secret', '');
        $this->migrator->addEncrypted('general.zoom_account_id', '');

        $this->migrator->add('general.watermark_text', '');
        $this->migrator->add('general.watermark_enabled', false);
        $this->migrator->add('general.watermark_position', 'bottom-right');
        $this->migrator->add('general.watermark_font', 'Arial');

        $this->migrator->add('general.google_analytics_tracking_id', '');
        $this->migrator->add('general.google_tag_manager_id', '');
        $this->migrator->add('general.facebook_pixel_id', '');

        $this->migrator->addEncrypted('general.mailchimp_api_key', '');
        $this->migrator->addEncrypted('general.mailchimp_list_id', '');
        $this->migrator->add('general.mailchimp_email', '');
        $this->migrator->add('general.mailchimp_name', '');

        $this->migrator->add('general.smtp_host', '');
        $this->migrator->add('general.smtp_port', '');
        $this->migrator->add('general.smtp_username', '');
        $this->migrator->addEncrypted('general.smtp_password', '');
        $this->migrator->add('general.smtp_encryption', '');
        $this->migrator->add('general.smtp_from_address', '');
        $this->migrator->add('general.smtp_from_name', '');
    }
};
