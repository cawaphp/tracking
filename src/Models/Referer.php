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
use Cawa\Models\Commons\Event as ModelEvent;
use Cawa\Orm\Model;

class Referer extends Model
{
    use DatabaseFactory;
    use DispatcherFactory;

    const MODEL_TYPE = 'REFERER';

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
        if ($url !== $this->url) {
            $this->url = $url;

            $this->addChangedProperties('url', $url);
        }

        return $this;
    }

    //endregion

    //region Db Read

    /**
     * @param int $id
     *
     * @return $this|self|null
     */
    public static function getById(int $id)
    {
        $db = self::db(self::class);

        $sql = 'SELECT *
                FROM tbl_tracking_referer
                WHERE referer_id = :id';
        if ($result = $db->fetchOne($sql, ['id' => $id])) {
            $return = new static();
            $return->map($result);

            return $return;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $result)
    {
        $this->id = $result['referer_id'];
        $this->url = $result['referer_url'];
    }

    //endregion

    //region Db Alter

    /**
     * @param int $userId
     *
     * @return bool
     */
    private function insert(int $userId = null) : bool
    {
        $db = self::db(self::class);

        $sql = 'INSERT INTO tbl_tracking_referer
                SET referer_url = :url';

        $result = $db->query($sql, [
            'url' => $this->url,
        ]);

        $this->id = $result->insertedId();

        self::emit(new ModelEvent('model.insert', $this, $userId));

        return true;
    }

    /**
     * @param int $userId
     *
     * @return bool
     */
    private function update(int $userId = null) : bool
    {
        $db = self::db(self::class);

        $sql = 'UPDATE tbl_tracking_referer
                SET referer_url = :url
                WHERE referer_id = :id';

        $db->query($sql, [
            'id' => $this->id,
            'url' => $this->url,
        ]);

        self::emit(new ModelEvent('model.update', $this, $userId));

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

        $sql = 'DELETE FROM tbl_tracking_referer
                WHERE referer_id = :id';

        $db->query($sql, [
            'id' => $this->id,
        ]);

        self::emit(new ModelEvent('model.delete', $this, $userId));

        return true;
    }

    //endregion
}
