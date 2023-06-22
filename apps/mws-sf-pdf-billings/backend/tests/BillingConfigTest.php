<?php

namespace App\Tests;

use Symfony\Component\Panther\PantherTestCase;

class BillingConfigTest extends PantherTestCase
{
    public function testDefaultConfig(): void
    {
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/');

        $this->assertSelectorTextContains('[for="billing_config_submitable_businessWorkloadHours"]', 'Business workload hours');
        $client->takeScreenshot('tests/outputs/BillingConfig-default.png'); // Yeah, screenshot!

        // // https://github.com/symfony/panther
        // $client = Client::createChromeClient();
        // // Or, if you care about the open web and prefer to use Firefox
        // $client = Client::createFirefoxClient();
        
        // $client->request('GET', 'https://api-platform.com'); // Yes, this website is 100% written in JavaScript
        // $client->clickLink('Get started');
        
        // // Wait for an element to be present in the DOM (even if hidden)
        // $crawler = $client->waitFor('#installing-the-framework');
        // // Alternatively, wait for an element to be visible
        // $crawler = $client->waitForVisibility('#installing-the-framework');
        
        // echo $crawler->filter('#installing-the-framework')->text();
        // $client->takeScreenshot('screen.png'); // Yeah, screenshot!
    }
}
