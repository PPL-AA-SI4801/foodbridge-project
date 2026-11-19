<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    protected function newBrowser($driver)
    {
        return new SlowBrowser($driver);
    }

    /**
     * Determine if headless mode is disabled.
     */
    protected function hasHeadlessDisabled(): bool
    {
        return parent::hasHeadlessDisabled()
            || filter_var(env('DUSK_HEADLESS_DISABLED', false), FILTER_VALIDATE_BOOLEAN)
            || filter_var(env('DUSK_HEADLESS_DISABLE', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            '--window-size=1365,900',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--no-sandbox',
            '--disable-dev-shm-usage',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        $chromeBinary = env('DUSK_CHROME_BINARY') ?: getenv('DUSK_CHROME_BINARY') ?: null;
        if (is_string($chromeBinary) && $chromeBinary !== '' && is_file($chromeBinary)) {
            $options->setBinary($chromeBinary);
        }

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Keep the browser window at the configured presentation size.
     */
    protected function shouldStartMaximized(): bool
    {
        return false;
    }
}
