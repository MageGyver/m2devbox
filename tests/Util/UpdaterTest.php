<?php
/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace Util;

use MageGyver\M2devbox\Util\Updater;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Devbox\Util\Updater
 */
class UpdaterTest extends TestCase
{

    public function testIsNewerVersion()
    {
        // [latest release, current version, expected result]
        $versions = [
            ['0.0.2', '0.0.1', true],
            ['0.2.0', '0.1.0', true],
            ['0.2.0', '0.0.1', true],
            ['11.2.0', '1.2.0', true],
            ['0.1.0', '1.2.0', false],
        ];

        foreach ($versions as $test) {
            $actual = Updater::isNewerVersion($test[0], $test[1]);
            $this->assertEquals($test[2], $actual);
        }
    }

    public function testExtractReleaseInfo()
    {
        $json = <<<JSON
{
  "url": "https://api.github.com/repos/MageGyver/m2devbox/releases/39905077",
  "assets_url": "https://api.github.com/repos/MageGyver/m2devbox/releases/39905077/assets",
  "upload_url": "https://uploads.github.com/repos/MageGyver/m2devbox/releases/39905077/assets{?name,label}",
  "html_url": "https://github.com/MageGyver/m2devbox/releases/tag/0.2.1",
  "id": 39905077,
  "author": {
    "login": "aeno",
    "id": 598616,
    "node_id": "MDQ6VXNlcjU5ODYxNg==",
    "avatar_url": "https://avatars.githubusercontent.com/u/598616?v=4",
    "gravatar_id": "",
    "url": "https://api.github.com/users/aeno",
    "html_url": "https://github.com/aeno",
    "followers_url": "https://api.github.com/users/aeno/followers",
    "following_url": "https://api.github.com/users/aeno/following{/other_user}",
    "gists_url": "https://api.github.com/users/aeno/gists{/gist_id}",
    "starred_url": "https://api.github.com/users/aeno/starred{/owner}{/repo}",
    "subscriptions_url": "https://api.github.com/users/aeno/subscriptions",
    "organizations_url": "https://api.github.com/users/aeno/orgs",
    "repos_url": "https://api.github.com/users/aeno/repos",
    "events_url": "https://api.github.com/users/aeno/events{/privacy}",
    "received_events_url": "https://api.github.com/users/aeno/received_events",
    "type": "User",
    "site_admin": false
  },
  "node_id": "MDc6UmVsZWFzZTM5OTA1MDc3",
  "tag_name": "0.2.1",
  "target_commitish": "main",
  "name": "m2devbox v0.2.1",
  "draft": false,
  "prerelease": false,
  "created_at": "2021-03-16T20:35:13Z",
  "published_at": "2021-03-16T20:41:15Z",
  "assets": [
    {
      "url": "https://api.github.com/repos/MageGyver/m2devbox/releases/assets/33554803",
      "id": 33554803,
      "node_id": "MDEyOlJlbGVhc2VBc3NldDMzNTU0ODAz",
      "name": "m2devbox.phar",
      "label": null,
      "uploader": {
        "login": "aeno",
        "id": 598616,
        "node_id": "MDQ6VXNlcjU5ODYxNg==",
        "avatar_url": "https://avatars.githubusercontent.com/u/598616?v=4",
        "gravatar_id": "",
        "url": "https://api.github.com/users/aeno",
        "html_url": "https://github.com/aeno",
        "followers_url": "https://api.github.com/users/aeno/followers",
        "following_url": "https://api.github.com/users/aeno/following{/other_user}",
        "gists_url": "https://api.github.com/users/aeno/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/aeno/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/aeno/subscriptions",
        "organizations_url": "https://api.github.com/users/aeno/orgs",
        "repos_url": "https://api.github.com/users/aeno/repos",
        "events_url": "https://api.github.com/users/aeno/events{/privacy}",
        "received_events_url": "https://api.github.com/users/aeno/received_events",
        "type": "User",
        "site_admin": false
      },
      "content_type": "application/octet-stream",
      "state": "uploaded",
      "size": 277465,
      "download_count": 4,
      "created_at": "2021-03-16T20:41:10Z",
      "updated_at": "2021-03-16T20:41:12Z",
      "browser_download_url": "https://github.com/MageGyver/m2devbox/releases/download/0.2.1/m2devbox.phar"
    }
  ],
  "tarball_url": "https://api.github.com/repos/MageGyver/m2devbox/tarball/0.2.1",
  "zipball_url": "https://api.github.com/repos/MageGyver/m2devbox/zipball/0.2.1",
  "body": "New: support for all Magento versions from 2.3.4 to 2.4.2"
}
JSON;


        $actual = Updater::extractReleaseInfo($json);
        $this->assertIsArray($actual);
        $this->assertArrayHasKey('version', $actual);
        $this->assertArrayHasKey('download', $actual);
        $this->assertNotEmpty($actual['version']);
        $this->assertNotEmpty($actual['download']);
        $this->assertStringStartsWith('http', $actual['download']);
    }

    public function testFaultyExtractReleaseInfo()
    {
        $json = "foobar";
        $this->expectException(Exception::class);
        Updater::extractReleaseInfo($json);

        $json = '{"foo": "bar"}';
        $this->expectException(Exception::class);
        Updater::extractReleaseInfo($json);

        $json = '{"tag_name": "0.1.0"}';
        $this->expectException(Exception::class);
        Updater::extractReleaseInfo($json);

        $json = '{"tag_name": "0.1.0", "assets": "bar"}';
        $this->expectException(Exception::class);
        Updater::extractReleaseInfo($json);

        $json = '{"tag_name": "0.1.0", "assets": [{"foo":"bar"}]}';
        $this->expectException(Exception::class);
        Updater::extractReleaseInfo($json);

        $json = '{"tag_name": "0.1.0", "assets": [{"browser_download_url": "http://example.com"}]}';
        $this->expectException(Exception::class);
        Updater::extractReleaseInfo($json);
    }

    public function testDownloadReleaseInfo()
    {
        $actual = Updater::downloadReleaseInfo('https://example.com');
        $this->assertNotEmpty($actual);
    }

    public function testFaultyDownloadReleaseInfo()
    {
        $this->expectException(Exception::class);
        Updater::downloadReleaseInfo('error://example');
    }
}
