<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->migrator->add('network_router.ip_address', '');
        $this->migrator->add('network_router.port', 8728);
        $this->migrator->add('network_router.admin_username', 'admin');
        $this->migrator->add('network_router.admin_password', '');
        $this->migrator->add('network_router.hotspot_profile', 'default');
        $this->migrator->add('network_router.hotspot_server', 'hotspot1');
        $this->migrator->add('network_router.connection_timeout', 10);
        $this->migrator->add('network_router.use_ssl', false);
        $this->migrator->add('network_router.enabled', false);
        $this->migrator->add('network_router.username_format', '{phone}');
        $this->migrator->add('network_router.auto_generate_password', true);
        $this->migrator->add('network_router.password_length', 8);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->migrator->delete('network_router.ip_address');
        $this->migrator->delete('network_router.port');
        $this->migrator->delete('network_router.admin_username');
        $this->migrator->delete('network_router.admin_password');
        $this->migrator->delete('network_router.hotspot_profile');
        $this->migrator->delete('network_router.hotspot_server');
        $this->migrator->delete('network_router.connection_timeout');
        $this->migrator->delete('network_router.use_ssl');
        $this->migrator->delete('network_router.enabled');
        $this->migrator->delete('network_router.username_format');
        $this->migrator->delete('network_router.auto_generate_password');
        $this->migrator->delete('network_router.password_length');
    }
};
