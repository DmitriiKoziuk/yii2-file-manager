<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\entities;

use Yii;
use yii\db\ActiveQuery;
use yii\queue\cli\Queue;
use yii\helpers\Inflector;
use yii\behaviors\TimestampBehavior;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;
use DmitriiKoziuk\yii2FileManager\helpers\FileHelper;
use DmitriiKoziuk\yii2FileManager\jobs\ThumbnailImagesJob;

/**
 * This is the model class for table "{{%dk_fm_files}}".
 *
 * @property int $id
 * @property int $entity_group_id
 * @property int $mime_type_id
 * @property int $specific_entity_id
 * @property string $location_alias
 * @property string $name
 * @property string $real_name
 * @property int $size
 * @property int $sort
 * @property int $created_at
 * @property int $updated_at
 *
 * @property GroupEntity $entityGroup
 * @property MimeTypeEntity $mimeType
 * @property ImageEntity $image
 */
class FileEntity extends \yii\db\ActiveRecord
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
        return '{{%dk_fm_files}}';
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
            [
                [
                    'entity_group_id', 'mime_type_id', 'specific_entity_id', 'location_alias',
                    'name', 'real_name', 'size', 'sort'
                ],
                'required'
            ],
            [['entity_group_id', 'mime_type_id', 'specific_entity_id', 'size', 'sort', 'created_at', 'updated_at'], 'integer'],
            [['location_alias'], 'string', 'max' => 25],
            [['name'], 'string', 'max' => 155],
            [['real_name'], 'string', 'max' => 255],
            [
                ['entity_group_id', 'specific_entity_id', 'sort'],
                'unique',
                'targetAttribute' => ['entity_group_id', 'specific_entity_id', 'sort']
            ],
            [
                ['entity_group_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => GroupEntity::class,
                'targetAttribute' => ['entity_group_id' => 'id']
            ],
            [
                ['mime_type_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => MimeTypeEntity::class,
                'targetAttribute' => ['mime_type_id' => 'id']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t(FileManagerModule::TRANSLATE, 'ID'),
            'entity_group_id' => Yii::t(FileManagerModule::TRANSLATE, 'Entity Group ID'),
            'mime_type_id' => Yii::t(FileManagerModule::TRANSLATE, 'Mime Type ID'),
            'specific_entity_id' => Yii::t(FileManagerModule::TRANSLATE, 'Specific Entity ID'),
            'location_alias' => Yii::t(FileManagerModule::TRANSLATE, 'Location Alias'),
            'name' => Yii::t(FileManagerModule::TRANSLATE, 'Name'),
            'real_name' => Yii::t(FileManagerModule::TRANSLATE, 'Real Name'),
            'size' => Yii::t(FileManagerModule::TRANSLATE, 'Size'),
            'sort' => Yii::t(FileManagerModule::TRANSLATE, 'Sort'),
            'created_at' => Yii::t(FileManagerModule::TRANSLATE, 'Created At'),
            'updated_at' => Yii::t(FileManagerModule::TRANSLATE, 'Updated At'),
        ];
    }

    /**
     * Gets query for [[EntityGroup]].
     *
     * @return ActiveQuery
     */
    public function getEntityGroup()
    {
        return $this->hasOne(GroupEntity::class, ['id' => 'entity_group_id']);
    }

    /**
     * Gets query for [[MimeType]].
     *
     * @return ActiveQuery
     */
    public function getMimeType()
    {
        return $this->hasOne(MimeTypeEntity::class, ['id' => 'mime_type_id']);
    }

    /**
     * Gets query for [[DkFmImages]].
     *
     * @return ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(ImageEntity::class, ['file_id' => 'id']);
    }

    public function isImage()
    {
        return $this->mimeType->type == 'image';
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

    public function getUrl()
    {
        $webFolder = $this::getUploadFileWebFolder(
            $this->entityGroup->module_name,
            $this->entityGroup->entity_name,
            $this->specific_entity_id
        );
        return $webFolder . '/' . $this->name;
    }

    public static function getUploadFileWebFolder(string $moduleName, string $entityName, int $specificEntityID)
    {
        return "/uploads/{$moduleName}/{$entityName}/{$specificEntityID}";
    }

    public static function getUploadFileFolderFullPath(
        string $location,
        string $moduleName,
        string $entityName,
        int $specificEntityID
    ) {
        return Yii::getAlias($location) . self::getUploadFileWebFolder($moduleName, $entityName, $specificEntityID);
    }

    public static function prepareFilename(string $fileName): string
    {
        $fileName = trim($fileName);
        $fileName = Inflector::transliterate($fileName);
        $fileName = mb_strtolower($fileName);
        $fileName = preg_replace("/[^a-z0-9.\s]/","-", $fileName);
        $fileName = preg_replace('/\s{1,}/', '-', $fileName);
        $fileName = preg_replace('/[-]{2,}/', '-', $fileName);
        return trim($fileName, '-');
    }
}
