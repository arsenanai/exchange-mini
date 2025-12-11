<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Foundation\Application;
use Laravel\Dusk\TestCase as BaseTestCase;
use Symfony\Component\Process\Process;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication {
        createApplication as baseCreateApplication;
    }

    public function createApplication(): Application
    {
        $app = $this->baseCreateApplication();
        // Force the broadcast driver to 'pusher' for Dusk tests
        // to ensure real-time events are actually broadcasted.
        $app['config']->set('broadcasting.default', 'pusher');
        // Force the queue driver to 'sync' to ensure broadcast events are
        // sent immediately without needing a queue worker.
        $app['config']->set('queue.default', 'sync');

        return $app;
    }

    /**
     * Prepare for Dusk test execution. This method is called once before any tests in the class.
     *
     * @beforeClass
     */
    public static function prepare(): void
    {
        // Since we are manually starting and stopping ChromeDriver in composer.json,
        // we will disable Dusk's automatic process management to prevent conflicts.
        // if (env('DUSK_BROWSER', 'chrome') === 'chrome' && ! static::runningInSail()) {
        //     static::startChromeDriver();
        // }
    }

    /**
     * Perform setup tasks before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Any per-test setup can go here. ChromeDriver is already started by prepare().
    }

    /**
     * Create the RemoteWebDriver instance based on the DUSK_BROWSER environment variable.
     */
    protected function driver(): RemoteWebDriver
    {
        // Use Safari if specified
        if (config('dusk.browser') === 'safari') {
            // The default URL for safaridriver is often http://localhost:4444
            // If you have issues, you might need to start it manually with `safaridriver -p 4444`
            return RemoteWebDriver::create(
                'http://localhost:4444',
                DesiredCapabilities::safari()
            );
        }

        // Default to Chrome (headless by default)
        $options = (new ChromeOptions())->addArguments([
            '--disable-gpu',
            '--window-size=1920,1080',
        ]);
        // Add headless only if env is not set to headed mode
        if (! config('dusk.headed_mode')) {
            $options->addArguments(['--headless']);
        }

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY,
                $options
            )
        );
    }
}
