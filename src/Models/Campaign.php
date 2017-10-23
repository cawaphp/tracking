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
use Cawa\Events\DispatcherFactory;
use Cawa\Models\Commons\Event;
use Cawa\Models\Properties\ParentTrait;
use Cawa\Orm\Collection;
use Cawa\Orm\Model;
use Cawa\Tracking\Properties\AnalyticKeyInterface;
use Cawa\Tracking\Properties\AnalyticKeyTrait;

class Campaign extends Model implements AnalyticKeyInterface
{
    use DatabaseFactory;
    use DispatcherFactory;
    use AnalyticKeyTrait;
    use ParentTrait;

    const MODEL_TYPE = 'CAMPAIGN';

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
                FROM tbl_tracking_campaign
                WHERE campaign_id = :id
                    AND campaign_deleted IS NULL';
        if ($result = $db->fetchOne($sql, ['id' => $id])) {
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
    public static function getByParentId(int $id) : Collection
    {
        $return = [];

        $db = self::db(self::class);
        $sql = 'SELECT *
                FROM tbl_tracking_campaign
                WHERE campaign_parent_id = :id
                    AND campaign_deleted IS NULL';
        foreach ($db->query($sql, ['id' => $id]) as $result) {
            $item = new static();
            $item->map($result);
            $return[] = $item;
        }

        $collection = new Collection($return);

        return $collection;
    }

    /**
     * @return $this[]|Collection
     */
    public static function getAll() : Collection
    {
        $return = [];

        $db = self::db(self::class);
        $sql = 'SELECT *
                FROM tbl_tracking_campaign
                WHERE campaign_deleted IS NULL
                ORDER BY IFNULL(campaign_parent_id, campaign_id) DESC, IFNULL(campaign_parent_id, 0), campaign_name ASC';
        foreach ($db->query($sql) as $result) {
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
        $this->id = $result['campaign_id'];
        $this->parentId = $result['campaign_parent_id'];
        $this->name = $result['campaign_name'];
        $this->analyticKey = $result['campaign_analytic_key'];
    }

    //endregion

    //region Db Alter

    /**
     * @param int $userId
     *
     * @return bool
     */
    public function insert(int $userId = null) : bool
    {
        $db = self::db(self::class);

        $sql = 'INSERT INTO tbl_tracking_campaign
                SET campaign_parent_id = :parentId,
                    campaign_name = :name,
                    campaign_analytic_key = :analyticKey';

        $result = $db->query($sql, [
            'parentId' => $this->parentId,
            'name' => $this->name,
            'analyticKey' => $this->analyticKey,
        ]);

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

        $sql = 'UPDATE tbl_tracking_campaign
                SET campaign_parent_id = :parentId,
                    campaign_name = :name,
                    campaign_analytic_key = :analyticKey
                WHERE campaign_id = :id';

        $result = $db->query($sql, [
            'parentId' => $this->parentId,
            'name' => $this->name,
            'id' => $this->id,
            'analyticKey' => $this->analyticKey,
        ]);

        $this->id = $result->insertedId();

        self::emit(new Event('model.update', $this, $userId));

        return true;
    }

    /**
     * @param int $userId
     *
     * @return bool
     */
    public function delete(int $userId = null)
    {
        $db = self::db(self::class);

        $sql = 'UPDATE tbl_tracking_campaign
                SET campaign_deleted = NOW()
                WHERE campaign_id = :id';

        $result = $db->query($sql, [
            'id' => $this->id,
        ]);

        self::emit(new Event('model.delete', $this, $userId));

        foreach (self::getByParentId($this->id) as $child) {
            $child->delete($userId);
        }

        return $result->affectedRows() > 0;
    }

    //endregion
}
