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

namespace Cawa\Tracking\Models\Logs;

use Cawa\Date\DateTime;
use Cawa\Db\DatabaseFactory;
use Cawa\Net\Ip;
use Cawa\Tracking\Properties\LinkTrait;

class Click extends AbstractLog
{
    use DatabaseFactory;
    use LinkTrait;

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
    private $ip;

    /**
     * @return string
     */
    public function getIp() : string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return $this|self
     */
    public function setIp(string $ip = null) : self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @var array
     */
    private $data;

    /**
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return $this|self
     */
    public function setData(array $data) : self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @return DateTime
     */
    public function getDate() : DateTime
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getEncrypted() : string
    {
        $data = implode('|', [$this->id, $this->linkId]);

        return self::encrypt($data);
    }

    //endregion

    //region Db Read

    /**
     * @param string $data
     *
     * @return Click|null
     */
    public static function getByEncrypted(string $data) : ?Click
    {
        $data = self::decrypt($data);

        if ($data) {
            $explode = explode('|', $data);
            $return = self::getById((int) $explode[0]);

            if ($return && $return->linkId == $explode[1]) {
                return $return;
            }
        }

        return null;
    }

    /**
     * @param int $id
     *
     * @return $this|null
     */
    public static function getById(int $id) : ?Click
    {
        $db = self::db(self::class);

        $sql = 'SELECT * 
                FROM tbl_tracking_logs_click
                WHERE click_id = :id';
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
        $this->id = $result['click_id'];
        $this->linkId = $result['click_link_id'];
        $this->ip = Ip::fromLong($result['click_ip']);
        $this->data = $result['click_data'] ? self::decodeData($result['click_data']) : [];
        $this->date = $result['click_date'];
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

        $this->date = new DateTime();

        $sql = 'INSERT INTO tbl_tracking_logs_click
                SET click_link_id = :linkId,
                    click_ip = :ip,
                    click_data= :data,
                    click_date = :date';

        $result = $db->query($sql, [
            'linkId' => $this->linkId,
            'ip' => $this->ip ? Ip::toLong($this->ip) : null,
            'data' => $this->data ? self::encodeData($this->data) : null,
            'date' => $this->date,
        ]);

        $this->id = $result->insertedId();

        return true;
    }

    //endregion
}
