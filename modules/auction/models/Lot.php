<?php

namespace app\modules\auction\models;

use Yii;
use app\modules\users\models\User;
use vova07\fileapi\behaviors\UploadBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\debug\models\search\Log;

/**
 * Class Lot
 * @package app\modules\auction\models
 * Model Lot.
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $type_id
 * @property integer $status
 * @property string $name
 * @property string $metadata
 * @property string $description
 * @property integer $price_min
 * @property integer $price_step
 * @property integer $price_blitz
 * @property integer $time_bid
 * @property integer $time_elapsed
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property Bid $bids
 * @property Bid $bid
 * @property string $picture
 * @property integer $currentStatus
 * @property \app\modules\users\models\User $user
 */
class Lot extends ActiveRecord
{
    public $region_name;
    public $picture_url;
    public $item_id;

    private $_status;
    private $_last_bid;
    private $_picture;

    const TYPE_ITEM = 1;
    const TYPE_LAND = 2;
    const TYPE_PROJECT = 3;
    const TYPE_OTHER = 9;

    const STATUS_DRAFT = 2;
    const STATUS_PUBLISHED = 5;
    const STATUS_STARTED = 6;
    const STATUS_FINISHED = 7;
    const STATUS_CLOSED = 9;
    const STATUS_BLOCKED = 10;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auction_lot}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => TimestampBehavior::className(),
            'uploadBehavior' => [
                'class' => UploadBehavior::className(),
                'attributes' => [
                    'picture_url' => [
                        'path' => '@webroot/images/auction/',
                        'tempPath' => '@webroot/images/auction/tmp/',
                        'url' => '/images/auction'
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return [
            'create' => ['user_id', 'type_id', 'status', 'name', 'item_id', 'region_name', 'picture_url', 'metadata', 'description', 'price_min', 'price_step', 'price_blitz', 'time_bid', 'time_elapsed', 'created_at', 'updated_at'],
            'update' => ['status', 'name', 'item_id', 'region_name', 'picture_url', 'metadata', 'description', 'price_min', 'price_step', 'price_blitz', 'updated_at'],
            'updateActive' => ['description', 'updated_at'],
            'status' => ['status'],
            'delete-picture' => ['picture_url'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'price_min', 'metadata'], 'required'],

            [['name', 'metadata', 'description', 'region_name'], 'string'],
            [['time_bid', 'time_elapsed', 'status'], 'integer'],
            [['time_bid', 'created_at', 'updated_at'], 'safe'],

            [['name'], 'string', 'max' => 255],
            ['price_blitz', 'integer', 'max' => 1000000000],
            ['price_min', 'integer', 'max' => 10000000],
            ['price_step', 'integer', 'max' => 500000],
            ['price_min', 'default', 'value' => 1],

            ['status', 'default', 'value' => self::STATUS_PUBLISHED],
            ['status', 'in', 'range' => array_keys(self::getStatusArray())],

            ['type_id', 'in', 'range' => array_keys(self::getTypeArray())],

            ['user_id', 'default', 'value' => \Yii::$app->user->id],
            ['time_elapsed', 'default', 'value' => time() + 1209600], // 2 weeks
            ['time_bid', 'default', 'value' => 172800], // 2 days

            ['metadata', 'app\modules\auction\models\validators\MetadataValidator'],

            ['region_name', 'app\modules\auction\models\validators\LandValidator'],
            ['region_name', 'required', 'when' => function ($model) {
                return $model->type_id == self::TYPE_LAND;
            }, 'whenClient' => "function (attribute, value) {
                return $('#lot-type_id').val() == " . self::TYPE_LAND . ";
            }"],

            ['picture_url', 'app\modules\auction\models\validators\PictureValidator'],
            ['picture_url', 'required', 'when' => function ($model) {
                return ($model->type_id == self::TYPE_PROJECT || $model->type_id == self::TYPE_OTHER);
            }, 'whenClient' => "function (attribute, value) {
                var type = $('#lot-type_id').val();
                return (type == " . self::TYPE_PROJECT . " || type == " . self::TYPE_OTHER . ");
            }"],

            ['item_id', 'app\modules\auction\models\validators\ItemValidator'],
            ['item_id', 'required', 'when' => function ($model) {
                return $model->type_id == self::TYPE_ITEM;
            }, 'whenClient' => "function (attribute, value) {
                return $('#lot-type_id').val() == " . self::TYPE_ITEM . ";
            }"],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('auction', 'LOT_ID'),
            'user_id' => Yii::t('auction', 'LOT_USER_ID'),
            'name' => Yii::t('auction', 'LOT_NAME'),
            'metadata' => Yii::t('auction', 'LOT_METADATA'),
            'type_id' => Yii::t('auction', 'LOT_TYPE_ID'),
            'status' => Yii::t('auction', 'LOT_STATUS'),
            'description' => Yii::t('auction', 'LOT_DESCRIPTION'),
            'price_min' => Yii::t('auction', 'LOT_PRICE_MIN'),
            'price_step' => Yii::t('auction', 'LOT_PRICE_STEP'),
            'price_blitz' => Yii::t('auction', 'LOT_PRICE_BLITZ'),
            'time_bid' => Yii::t('auction', 'LOT_TIME_BID'),
            'time_elapsed' => Yii::t('auction', 'LOT_TIME_ELAPSED'),
            'created_at' => Yii::t('auction', 'LOT_CREATED_AT'),
            'updated_at' => Yii::t('auction', 'LOT_UPDATED_AT'),

            'picture_url' => Yii::t('auction', 'LOT_PICTURE_URL'),
            'item_id' => Yii::t('auction', 'LOT_ITEM_ID'),
            'region_name' => Yii::t('auction', 'LOT_REGION_NAME'),
        ];
    }

    /**
     * @return array
     */
    public static function getTypeArray()
    {
        return [
            self::TYPE_ITEM => Yii::t('auction', 'LOT_TYPE_ITEM'),
            self::TYPE_LAND => Yii::t('auction', 'LOT_TYPE_LAND'),
            self::TYPE_PROJECT => Yii::t('auction', 'LOT_TYPE_PROJECT'),
            self::TYPE_OTHER => Yii::t('auction', 'LOT_TYPE_OTHER'),
        ];
    }

    /**
     * @return array
     */
    public static function getStatusArray()
    {
        return [
            self::STATUS_PUBLISHED => Yii::t('auction', 'LOT_STATUS_PUBLISHED'),
            self::STATUS_DRAFT => Yii::t('auction', 'LOT_STATUS_DRAFT'),
            self::STATUS_STARTED => Yii::t('auction', 'LOT_STATUS_STARTED'),
            self::STATUS_FINISHED => Yii::t('auction', 'LOT_STATUS_FINISHED'),
            self::STATUS_CLOSED => Yii::t('auction', 'LOT_STATUS_CLOSED'),
            self::STATUS_BLOCKED => Yii::t('auction', 'LOT_STATUS_BLOCKED'),
        ];
    }

    /**
     * @return string
     */
    public function getType()
    {
        $type = self::getTypeArray();
        return $type[$this->type_id];
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        $type = self::getTypeArray();
        return $type[$this->type_id];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBid()
    {
        if(empty( $this->_last_bid )) {
            $this->_last_bid = $this->hasOne(Bid::className(), ['lot_id' => 'id'])->orderBy(['cost' => SORT_DESC])->one();
        }
        return $this->_last_bid;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBids()
    {
        return $this->hasMany(Bid::className(), ['lot_id' => 'id'])->orderBy(['cost' => SORT_DESC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return boolean
     */
    public function getIsEditable()
    {
        if ($this->isNewRecord || self::getCurrentStatus() === self::STATUS_DRAFT || self::getCurrentStatus() === self::STATUS_PUBLISHED || self::getCurrentStatus() === self::STATUS_CLOSED) {
            return true;
        }
        return false;
    }

    /**
     * @return integer
     */
    public function getCurrentStatus()
    {
        Yii::trace('Зашел: '  . $this->status);
        if (!empty($this->_status)) {
            return $this->_status;
        } elseif ($this->status === self::STATUS_BLOCKED || $this->status === self::STATUS_DRAFT || $this->status === self::STATUS_CLOSED || $this->status === self::STATUS_FINISHED) {
            return $this->status;
        } elseif ($this->status === self::STATUS_PUBLISHED || $this->status === self::STATUS_STARTED) {
            if ($this->time_elapsed < time()) {
                $this->_status = ($this->status === self::STATUS_STARTED || $this->bid) ? self::STATUS_FINISHED : self::STATUS_CLOSED;
            } elseif ($this->status === self::STATUS_STARTED || $this->bid) {
                if ($this->bid->cost >= $this->price_blitz) {
                    $this->_status = self::STATUS_FINISHED;
                } else {
                    $this->_status = self::STATUS_STARTED;
                }
            } else {
                return $this->status;
            }
        }

        $this->scenario = "status";
        $this->status = $this->_status;
        $this->save();

        Yii::trace('Вышел: '  . $this->status);

        return $this->_status;
    }

    public function afterFind()
    {
        $data = json_decode($this->metadata);

        if ($this->type_id == self::TYPE_LAND) {
            $this->region_name = $data->name;
        } if ($this->type_id == self::TYPE_ITEM) {
            $this->item_id = $data->item_id;
        }

        if(isset($data->picture_url)) {
            $this->picture_url = $data->picture_url;
        }
    }

    public function beforeSave($insert)
    {
        if(parent::beforeSave($insert)) {
            Yii::trace('time123: ' . time() . ', ' . $this->time_elapsed);
            if ($this->getIsEditable() && $this->time_elapsed < time()) {
                $this->time_elapsed += time();
                $this->status = self::STATUS_PUBLISHED;
            }
            return true;
        }
        return false;
    }

    public function getPicture()
    {
        if ($this->_picture === null) {
            $this->_picture = $this->picture_url ? ('/images/auction/'.$this->picture_url) : '/images/cap_image.png';
        }
        return $this->_picture;
    }
}
