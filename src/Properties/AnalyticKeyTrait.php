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

trait AnalyticKeyTrait
{
    /**
     * @var string
     */
    private $analyticKey;

    /**
     * @return string
     */
    public function getAnalyticKey() : ?string
    {
        return $this->analyticKey;
    }

    /**
     * @param string $analyticKey
     *
     * @return self|$this
     */
    public function setAnalyticKey(string $analyticKey = null) : self
    {
        $this->analyticKey = $analyticKey;

        return $this;
    }
}
