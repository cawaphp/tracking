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

use Cawa\Tracking\Models\Campaign;

trait CampaignTrait
{
    /**
     * @var int
     */
    private $campaignId;

    /**
     * @return int
     */
    public function getCampaignId() : int
    {
        return $this->campaignId;
    }

    /**
     * @param int $campaignId
     *
     * @return $this|self
     */
    public function setCampaignId(int $campaignId) : self
    {
        if ($this->campaign && $this->campaign->getId() != $campaignId) {
            $this->campaign = null;
        }

        if ($this->campaignId !== $campaignId) {
            $this->campaignId = $campaignId;

            $this->addChangedProperties('campaignId', $campaignId);
        }

        return $this;
    }

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @return Campaign
     */
    public function getCampaign() : Campaign
    {
        if (!$this->campaign) {
            $this->campaign = Campaign::getById($this->campaignId);
        }

        return $this->campaign;
    }

    /**
     * @param Campaign $campaign
     *
     * @return $this|self
     */
    public function setCampaign(Campaign $campaign) : self
    {
        $this->campaign = $campaign;
        $this->setCampaignId($campaign->getId());

        return $this;
    }
}
