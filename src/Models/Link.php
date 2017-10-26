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

namespace Cawa\Tracking\Models;

use Cawa\Db\DatabaseFactory;
use Cawa\Db\Exceptions\WarningException;
use Cawa\Db\Mysql\Mysql;
use Cawa\Events\DispatcherFactory;
use Cawa\Models\Commons\Event;
use Cawa\Net\Uri;
use Cawa\Orm\Collection;
use Cawa\Orm\Model;
use Cawa\Router\RouterFactory;
use Cawa\Tracking\Properties\AnalyticKeyInterface;
use Cawa\Tracking\Properties\AnalyticKeyTrait;
use Cawa\Tracking\Properties\CampaignTrait;
use Fhaculty\Graph\Exception\LogicException;

class Link extends Model implements AnalyticKeyInterface
{
    use DatabaseFactory;
    use DispatcherFactory;
    use RouterFactory;
    use CampaignTrait;
    use AnalyticKeyTrait;

    const MODEL_TYPE = 'LINK';

    //region Mutator

    /**
     * @var int
     */
    private $id;

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @var string
     */
    private $uid;

    /**
     * @return string
     */
    public function getUid() : string
    {
        return $this->uid;
    }

    /**
     * @var string
     */
    private $name;

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this|self
     */
    public function setName(string $name) : self
    {
        if ($this->name !== $name) {
            $this->name = $name;

            $this->addChangedProperties('name', $this->name);
        }

        return $this;
    }

    /**
     * @var string
     */
    private $url;

    /**
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return $this|self
     */
    public function setUrl(string $url) : self
    {
        if ($this->url !== $url) {
            $this->url = $url;

            $this->addChangedProperties('url', $this->url);
        }

        return $this;
    }

    //endregion

    //region Logic

    /**
     * @return Uri
     */
    public function getUri() : Uri
    {
        return self::uri('track', [
            'type' => 'c',
            'uid' => $this->getUid(),
        ]);
    }

    /**
     * @return string
     */
    private function generateUid() : string
    {
        $length = 6;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * @return array
     */
    public function getAnalyticData() : array
    {
        $return = $campaign = [];

        $collection = $this->getCampaign()->getParentTree();
        $collection->add($this);
        $collection = $collection->reverse();

        foreach ($collection as $item) {
            switch ($item->getAnalyticKey()) {
                case self::ANALYTIC_KEY_MEDIUM:
                    $return['campaignMedium'] = strtolower($item->getName());
                    break;
                case self::ANALYTIC_KEY_SOURCE:
                    $return['campaignSource'] = strtolower($item->getName());
                    break;
                case self::ANALYTIC_KEY_TERM:
                    $return['campaignKeyword'] = strtolower($item->getName());
                    break;
                case self::ANALYTIC_KEY_CONTENT:
                    $return['campaignContent'] = $item->getName();
                    break;
                case self::ANALYTIC_KEY_CAMPAIGN:
                    $return['campaignId'] = $item->getId();
                    $campaign[] = $item->getName();
                    break;
                default:
                    if (sizeof($campaign)) {
                        $campaign[] = $item->getName();
                    }
            }
        }

        if (sizeof($campaign) == 0) {
            throw new LogicException(sprintf("Invalid link '%s' analytic data", $this->id));
        }

        $campaign = array_reverse($campaign);
        $return['campaignName'] = implode('/', $campaign);

        return $return;
    }

    //endregion

    //region Db Read

    /**
     * @param int $id
     *
     * @return $this|null
     */
    public static function getById(int $id)
    {
        $db = self::db(self::class);

        $sql = 'SELECT * 
                FROM tbl_tracking_link
                WHERE link_id = :id
                    AND link_deleted IS NULL';
        if ($result = $db->fetchOne($sql, ['id' => $id])) {
            $return = new static();
            $return->map($result);

            return $return;
        }

        return null;
    }

    /**
     * @param string $uid
     *
     * @return $this|null
     */
    public static function getByUid(string $uid)
    {
        $db = self::db(self::class);

        $sql = 'SELECT * 
                FROM tbl_tracking_link
                WHERE link_uid = :uid
                    AND link_deleted IS NULL';
        if ($result = $db->fetchOne($sql, ['uid' => $uid])) {
            $return = new static();
            $return->map($result);

            return $return;
        }

        return null;
    }

    /**
     * @param int $id
     *
     * @return Collection|$this[]
     */
    public static function getByCampaignId(int $id) : Collection
    {
        $return = [];

        $db = self::db(self::class);
        $sql = 'SELECT *
                FROM tbl_tracking_link
                WHERE link_campaign_id = :id
                    AND link_deleted IS NULL';
        foreach ($db->query($sql, ['id' => $id]) as $result) {
            $item = new static();
            $item->map($result);
            $return[] = $item;
        }

        $collection = new Collection($return);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $result)
    {
        $this->id = $result['link_id'];
        $this->uid = $result['link_uid'];
        $this->campaignId = $result['link_campaign_id'];
        $this->name = $result['link_name'];
        $this->url = $result['link_url'];
        $this->analyticKey = $result['link_analytic_key'];
    }

    //endregion

    //region Db Alter

    /**
     * @param int $userId
     *
     * @throws WarningException
     *
     * @return bool
     */
    public function insert(int $userId = null) : bool
    {
        $db = self::db(self::class);

        $sql = 'INSERT INTO tbl_tracking_link
                SET link_uid = :uid,
                    link_campaign_id = :campaignId,
                    link_name = :name,
                    link_url = :url,
                    link_analytic_key = :analyticKey';

        $retry = true;
        while ($retry) {
            $this->uid = $this->generateUid();

            try {
                $result = $db->query($sql, [
                    'campaignId' => $this->campaignId,
                    'uid' => $this->uid,
                    'name' => $this->name,
                    'url' => $this->url,
                    'analyticKey' => $this->analyticKey,
                ]);

                $retry = false;
            } catch (WarningException $exception) {
                if (!$exception->isCode(Mysql::ERROR_DUPLICATE)) {
                    throw $exception;
                }
            }
        }

        $this->id = $result->insertedId();

        self::emit(new Event('model.insert', $this, $userId));

        return true;
    }

    /**
     * @param int $userId
     *
     * @return bool
     */
    public function update(int $userId = null) : bool
    {
        $db = self::db(self::class);

        $sql = 'UPDATE tbl_tracking_link
                SET link_campaign_id = :campaignId,
                    link_name = :name,
                    link_url = :url,
                    link_analytic_key = :analyticKey
                WHERE link_id = :id';

        $result = $db->query($sql, [
            'campaignId' => $this->campaignId,
            'name' => $this->name,
            'url' => $this->url,
            'id' => $this->id,
            'analyticKey' => $this->analyticKey,
        ]);

        $this->id = $result->insertedId();

        self::emit(new Event('model.update', $this, $userId));

        return true;
    }

    /**
     * @param int|null $userId
     *
     * @return bool
     */
    public function delete(int $userId = null)
    {
        $db = self::db(self::class);

        $sql = 'UPDATE tbl_tracking_link
                SET link_deleted = NOW()
                WHERE link_id = :id';

        $result = $db->query($sql, [
            'id' => $this->id,
        ]);

        self::emit(new Event('model.delete', $this, $userId));

        return $result->affectedRows() > 0;
    }

    //endregion
}
