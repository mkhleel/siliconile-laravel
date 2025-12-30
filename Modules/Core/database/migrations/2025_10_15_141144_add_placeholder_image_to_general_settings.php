<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.placeholder_image', null);
    }

    public function down(): void
    {
        $this->migrator->delete('general.placeholder_image');
    }
};
