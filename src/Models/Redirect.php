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
use Cawa\Http\Request;
use Cawa\Models\Commons\Event as ModelEvent;
use Cawa\Models\Properties\UserTrait;
use Cawa\Net\Uri;
use Cawa\Orm\Model;

class Redirect extends Model
{
    use DatabaseFactory;
    use DispatcherFactory;
    use UserTrait;

    const MODEL_TYPE = 'REDIRECT';

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
    private $match;

    /**
     * @return string
     */
    public function getMatch() : string
    {
        return $this->match;
    }

    /**
     * @param string $match
     *
     * @return $this|self
     */
    public function setMatch(string $match) : self
    {
        if ($match !== $this->match) {
            $this->match = $match;

            $this->addChangedProperties('match', $match);
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
                FROM tbl_tracking_redirect
                WHERE redirect_id = :id';
        if ($result = $db->fetchOne($sql, ['id' => $id])) {
            $return = new static();
            $return->map($result);

            return $return;
        }

        return null;
    }

    /**
     * @param Request $request
     *
     * @return Uri
     */
    public static function findMatch(Request $request) : ?Uri
    {
        $db = self::db(self::class);

        $sql = 'SELECT *
                FROM tbl_tracking_redirect
                WHERE :url REGEXP  redirect_match';
        foreach ($db->fetchAll($sql, ['url' => $request->getUri()->getPath()]) as $result) {
            $return = new static();
            $return->map($result);

            $path = preg_replace('`' . $return->getMatch() . '`', $return->getUrl(), $request->getUri()->getPath());

            $return = clone $request->getUri();
            $return->setPath($path);

            return $return;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function map(array $result)
    {
        $this->id = $result['redirect_id'];
        $this->match = $result['redirect_match'];
        $this->url = $result['redirect_url'];
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

        $sql = 'INSERT INTO tbl_tracking_redirect
                SET redirect_match = :match,
                    redirect_url = :url';

        $result = $db->query($sql, [
            'match' => $this->match,
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
    public function update(int $userId = null) : bool
    {
        $db = self::db(self::class);

        $sql = 'UPDATE tbl_tracking_redirect
                SET redirect_match = :match,
                    redirect_url = :url
                WHERE redirect_id = :id';

        $db->query($sql, [
            'id' => $this->id,
            'match' => $this->match,
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

        $sql = 'DELETE FROM tbl_tracking_redirect
                WHERE redirect_id = :id';

        $db->query($sql, [
            'id' => $this->id,
        ]);

        self::emit(new ModelEvent('model.delete', $this, $userId));

        return true;
    }

    //endregion
}
