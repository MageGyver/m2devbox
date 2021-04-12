#!/usr/bin/env php
<?php

/*
 * This file is part of the m2devbox project.
 * (c) Steffen Rieke <m2devbox@aenogym.de>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

namespace MageGyver\M2devbox;

require_once(__DIR__.'/src/bootstrap.php');

// init and run application
$devbox = new Devbox('Mage2 Devbox', M2D_VERSION);

/** @noinspection PhpUnhandledExceptionInspection */
$devbox->run();
