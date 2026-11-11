<?php

namespace App\Native;

use Symfony\UX\Native\Attribute\AsNativeConfiguration;
use Symfony\UX\Native\Attribute\AsNativeConfigurationProvider;
use Symfony\UX\Native\Configuration\Configuration;
use Symfony\UX\Native\Configuration\Rule;

/**
 * Path configuration for the iOS and Android shells (Hotwire Native, in native/).
 * Archives opens as a modal sheet: no template change, just these rules.
 *
 * The shells bundle a copy of these rules for their very first launch:
 * `make native-sync-config` refreshes it from this class.
 */
#[AsNativeConfigurationProvider]
final class AppNativeConfiguration
{
    #[AsNativeConfiguration('/config/ios_v1.json')]
    public function iosV1(): Configuration
    {
        return new Configuration(rules: [
            new Rule(
                patterns: ['.*'],
                properties: ['context' => 'default', 'pull_to_refresh_enabled' => true],
            ),
            new Rule(
                patterns: ['/archives$'],
                properties: ['context' => 'modal', 'pull_to_refresh_enabled' => false],
            ),
        ]);
    }

    /**
     * Android also needs the fragment that renders each screen ("uri").
     */
    #[AsNativeConfiguration('/config/android_v1.json')]
    public function androidV1(): Configuration
    {
        return new Configuration(rules: [
            new Rule(
                patterns: ['.*'],
                properties: [
                    'context' => 'default',
                    'uri' => 'hotwire://fragment/web',
                    'fallback_uri' => 'hotwire://fragment/web',
                    'pull_to_refresh_enabled' => true,
                ],
            ),
            new Rule(
                patterns: ['/archives$'],
                properties: [
                    'context' => 'modal',
                    'uri' => 'hotwire://fragment/web/modal/sheet',
                    'pull_to_refresh_enabled' => false,
                ],
            ),
        ]);
    }
}
