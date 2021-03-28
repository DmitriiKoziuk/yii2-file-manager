<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\services;

class SettingsService
{
    private array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function getFrontendDomain(): string
    {
        return $this->settings['frontendDomain'];
    }

    public function getBackendDomain(): string
    {
        return $this->settings['backendDomain'];
    }
}
