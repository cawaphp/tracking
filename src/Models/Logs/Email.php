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
use Cawa\Orm\Model;

class Email extends Model
{
    use DatabaseFactory;

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
                FROM tbl_tracking_logs_email
                WHERE email_id = :id';
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
        $this->id = $result['email_id'];
        $this->data = $result['email_data'] ? self::decodeData($result['email_data']) : [];
        $this->date = $result['email_date'];
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

        $sql = 'INSERT INTO tbl_tracking_logs_email
                SET email_data= :data,
                    email_date = :date';

        $result = $db->query($sql, [
            'data' => $this->data ? self::encodeData($this->data) : null,
            'date' => $this->date,
        ]);

        $this->id = $result->insertedId();

        return true;
    }

    //endregion
}
