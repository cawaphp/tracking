<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Cawa\Tracking\Properties;

interface AnalyticKeyInterface
{
    const ANALYTIC_KEY_CAMPAIGN = 'CAMPAIGN';
    const ANALYTIC_KEY_SOURCE = 'SOURCE';
    const ANALYTIC_KEY_MEDIUM = 'MEDIUM';
    const ANALYTIC_KEY_TERM = 'TERM';
    const ANALYTIC_KEY_CONTENT = 'CONTENT';
}
