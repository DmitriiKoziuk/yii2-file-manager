<?php

namespace DmitriiKoziuk\yii2FileManager\entities;

use Yii;
use yii\queue\cli\Queue;
use yii\db\ActiveRecord;
use yii\di\NotInstantiableException;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;
use DmitriiKoziuk\yii2FileManager\helpers\FileHelper;
use DmitriiKoziuk\yii2FileManager\jobs\ThumbnailImagesJob;

/**
 * This is the model class for table "{{%dk_files}}".
 *
 * @property int    $id
 * @property string $entity_name
 * @property string $entity_id
 * @property string $location_alias @frontend @backend etc.
 * @property string $mime_type
 * @property string $name File name without extension.
 * @property string $extension
 * @property int    $size In bytes.
 * @property string $title
 * @property int    $sort
 * @property int    $created_at
 * @property int    $updated_at
 *
 * @property Image $image
 */
class FileEntity extends ActiveRecord
{
    const FRONTEND_LOCATION_ALIAS = '@frontend';
    const BACKEND_LOCATION_ALIAS = '@backend';

    /**
     * @var FileHelper
     */
    private $fileHelper;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dk_files}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['entity_name', 'entity_id', 'location_alias', 'mime_type', 'title'], 'required'],
            [['size', 'sort', 'created_at', 'updated_at'], 'integer'],
            [['entity_name', 'entity_id'], 'string', 'max' => 45],
            [['location_alias', 'mime_type'], 'string', 'max' => 25],
            [['title'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 155],
            [['extension'], 'string', 'max' => 10],
            [
                ['entity_name', 'entity_id', 'sort'],
                'unique',
                'targetAttribute' => ['entity_name', 'entity_id', 'sort']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'             => Yii::t(FileManagerModule::ID, 'ID'),
            'entity_name'    => Yii::t(FileManagerModule::ID, 'Entity Name'),
            'entity_id'      => Yii::t(FileManagerModule::ID, 'Entity ID'),
            'location_alias' => Yii::t(FileManagerModule::ID, 'Location alias'),
            'mime_type'      => Yii::t(FileManagerModule::ID, 'Mime type'),
            'name'           => Yii::t(FileManagerModule::ID, 'Name'),
            'extension'      => Yii::t(FileManagerModule::ID, 'Extension'),
            'size'           => Yii::t(FileManagerModule::ID, 'Size'),
            'title'          => Yii::t(FileManagerModule::ID, 'Title'),
            'sort'           => Yii::t(FileManagerModule::ID, 'Sort'),
            'created_at'     => Yii::t(FileManagerModule::ID, 'Created at'),
            'updated_at'     => Yii::t(FileManagerModule::ID, 'Updated at'),
        ];
    }

    /**
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     */
    public function init()
    {
        parent::init();
        $this->fileHelper = Yii::$container->get(FileHelper::class);
        $this->queue = Yii::$app->dkFileManagerQueue;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(Image::class, ['file_id' => 'id']);
    }

    public function getThumbnail(int $width, int $height, int $quality = 65): string
    {
        if ($this->fileHelper->isThumbExist($this, $width, $height, $quality)) {
            return $this->fileHelper->getThumbnailWebPath($this, $width, $height, $quality);
        }
        $this->queue->push(new ThumbnailImagesJob([
            'fileId' => $this->id,
            'width' => $width,
            'height' => $height,
            'quality' => $quality,
        ]));
        return $this->fileHelper->getFileRecordWebPath($this);
    }
}
