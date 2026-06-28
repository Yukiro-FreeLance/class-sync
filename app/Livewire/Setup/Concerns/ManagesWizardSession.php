<?php

namespace App\Livewire\Setup\Concerns;

trait ManagesWizardSession
{
    protected const WIZARD_SESSION_KEY = 'setup.wizard';

    protected function wizardData(?string $key = null, mixed $default = null): mixed
    {
        $data = session(self::WIZARD_SESSION_KEY.'.data', []);

        if ($key === null) {
            return $data;
        }

        return data_get($data, $key, $default);
    }

    protected function setWizardData(string $key, mixed $value): void
    {
        $data = session(self::WIZARD_SESSION_KEY.'.data', []);
        data_set($data, $key, $value);
        session([self::WIZARD_SESSION_KEY.'.data' => $data]);
    }

    protected function mergeWizardData(array $values): void
    {
        $data = array_merge(session(self::WIZARD_SESSION_KEY.'.data', []), $values);
        session([self::WIZARD_SESSION_KEY.'.data' => $data]);
    }

    protected function clearWizardSession(): void
    {
        session()->forget(self::WIZARD_SESSION_KEY);
    }
}
