<?php
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace Devbox\Service;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Devbox\Service\Config
 */
class ConfigTest extends TestCase
{
    // this array is intentionally unsorted
    private array $config = [
        'supported_versions' => [
            '2.4.1' => [
                'recipe_class' => 'Mage24',
                'long_version' => '2.4.1',
                'short_version' => '241',
                'compose_files' => ['docker-compose.mage-2.4.yml'],
            ],
            '2.3.5' => [
                'recipe_class' => 'Mage23',
                'long_version' => '2.3.5',
                'short_version' => '235',
                'compose_files' => ['docker-compose.mage-2.3.yml'],
            ],
            '2.3.6' => [
                'recipe_class' => 'Mage23',
                'long_version' => '2.3.6',
                'short_version' => '236',
                'compose_files' => ['docker-compose.mage-2.3.yml'],
            ],

        ],
    ];

    public function testLoad()
    {
        $path = tempnam('/tmp', 'm2d');
        file_put_contents($path, '<?php return '.var_export($this->config, true).';');

        Config::load($path);

        $result = Config::get('supported_versions');
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $this->assertArrayHasKey('2.3.5', $result);
        $this->assertArrayHasKey('2.3.6', $result);
        $this->assertArrayHasKey('2.4.1', $result);

        $this->assertArrayHasKey('recipe_class', $result['2.3.5']);
        $this->assertArrayHasKey('long_version', $result['2.3.6']);
        $this->assertArrayHasKey('short_version', $result['2.4.1']);
        $this->assertArrayHasKey('compose_files', $result['2.4.1']);

        $this->assertEquals('Mage23', $result['2.3.5']['recipe_class']);
        $this->assertEquals('2.3.6', $result['2.3.6']['long_version']);
        $this->assertEquals('241', $result['2.4.1']['short_version']);

        $this->assertEquals(['docker-compose.mage-2.3.yml'], $result['2.3.6']['compose_files']);

        unlink($path);
    }

    public function testLoadFromArray()
    {
        Config::loadFromArray($this->config);

        $result = Config::get('supported_versions');
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $this->assertArrayHasKey('2.3.5', $result);
        $this->assertArrayHasKey('2.3.6', $result);
        $this->assertArrayHasKey('2.4.1', $result);

        $this->assertArrayHasKey('recipe_class', $result['2.3.5']);
        $this->assertArrayHasKey('long_version', $result['2.3.6']);
        $this->assertArrayHasKey('short_version', $result['2.4.1']);
        $this->assertArrayHasKey('compose_files', $result['2.4.1']);

        $this->assertEquals('Mage23', $result['2.3.5']['recipe_class']);
        $this->assertEquals('2.3.6', $result['2.3.6']['long_version']);
        $this->assertEquals('241', $result['2.4.1']['short_version']);

        $this->assertEquals(['docker-compose.mage-2.3.yml'], $result['2.3.6']['compose_files']);
    }

    public function testGet()
    {
        Config::loadFromArray($this->config);
        $result = Config::get('supported_versions');
        $this->assertNotEmpty( $result);
    }

    public function testGetRecipes()
    {
        Config::loadFromArray($this->config);
        $recipes = Config::getRecipes();

        $this->assertIsArray($recipes);
        $this->assertNotEmpty($recipes);
    }
}
