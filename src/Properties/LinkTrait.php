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

use Cawa\Tracking\Models\Link;

trait LinkTrait
{
    /**
     * @var int
     */
    private $linkId;

    /**
     * @return int
     */
    public function getLinkId() : int
    {
        return $this->linkId;
    }

    /**
     * @param int $linkId
     *
     * @return $this|self
     */
    public function setLinkId(int $linkId) : self
    {
        if ($this->link && $this->link->getId() != $linkId) {
            $this->link = null;
        }

        if ($this->linkId !== $linkId) {
            $this->linkId = $linkId;

            $this->addChangedProperties('linkId', $linkId);
        }

        return $this;
    }

    /**
     * @var Link
     */
    private $link;

    /**
     * @return Link
     */
    public function getLink() : Link
    {
        if (!$this->link) {
            $this->link = Link::getById($this->linkId);
        }

        return $this->link;
    }

    /**
     * @param Link $link
     *
     * @return $this|self
     */
    public function setLink(Link $link) : self
    {
        $this->link = $link;
        $this->setLinkId($link->getId());

        return $this;
    }
}
