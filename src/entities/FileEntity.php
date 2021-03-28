<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\entities;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;
use yii\queue\cli\Queue;
use yii\helpers\Inflector;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;
use DmitriiKoziuk\yii2FileManager\interfaces\FileInterface;
use DmitriiKoziuk\yii2FileManager\jobs\ThumbnailImagesJob;
use DmitriiKoziuk\yii2FileManager\services\SettingsService;

/**
 * @property int    $id
 * @property int    $entity_group_id
 * @property int    $mime_type_id
 * @property int    $specific_entity_id
 * @property string $location_alias
 * @property string $directory
 * @property string $name
 * @property string $real_name
 * @property int    $size
 * @property int    $sort
 * @property string $created_at
 * @property string $updated_at
 *
 * @property GroupEntity    $entityGroup
 * @property MimeTypeEntity $mimeType
 * @property ImageEntity    $image
 */
class FileEntity extends ActiveRecord implements FileInterface
{
    const FRONTEND_LOCATION_ALIAS = '@frontend';
    const BACKEND_LOCATION_ALIAS  = '@backend';

    private Queue $queue;

    private SettingsService $settings;

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        /** @var Queue $q */
        $q = Yii::$app->get('dkFileManagerQueue');
        $this->queue = $q;
        /** @var SettingsService $s */
        $s = Yii::$container->get(SettingsService::class);
        $this->settings = $s;
        parent::init();
    }

    public static function tableName(): string
    {
        return '{{%dk_fm_files}}';
    }

    public function rules(): array
    {
        return [
            [
                [
                    'entity_group_id', 'mime_type_id', 'specific_entity_id', 'location_alias',
                    'directory', 'name', 'real_name', 'size', 'sort'
                ],
                'required'
            ],
            [['entity_group_id', 'mime_type_id', 'specific_entity_id', 'size', 'sort'], 'integer'],
            [['location_alias'], 'string', 'max' => 25],
            [['directory', 'name', 'real_name'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'date', 'format' => 'php:Y-m-d H:i:s'],
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

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t(FileManagerModule::TRANSLATE, 'ID'),
            'entity_group_id' => Yii::t(FileManagerModule::TRANSLATE, 'Entity Group ID'),
            'mime_type_id' => Yii::t(FileManagerModule::TRANSLATE, 'Mime Type ID'),
            'specific_entity_id' => Yii::t(FileManagerModule::TRANSLATE, 'Specific Entity ID'),
            'location_alias' => Yii::t(FileManagerModule::TRANSLATE, 'Location Alias'),
            'directory' => Yii::t(FileManagerModule::TRANSLATE, 'Name'),
            'name' => Yii::t(FileManagerModule::TRANSLATE, 'Name'),
            'real_name' => Yii::t(FileManagerModule::TRANSLATE, 'Real Name'),
            'size' => Yii::t(FileManagerModule::TRANSLATE, 'Size'),
            'sort' => Yii::t(FileManagerModule::TRANSLATE, 'Sort'),
            'created_at' => Yii::t(FileManagerModule::TRANSLATE, 'Created At'),
            'updated_at' => Yii::t(FileManagerModule::TRANSLATE, 'Updated At'),
        ];
    }

    public function getEntityGroup(): ActiveQuery
    {
        return $this->hasOne(GroupEntity::class, ['id' => 'entity_group_id']);
    }

    public function getMimeType(): ActiveQuery
    {
        return $this->hasOne(MimeTypeEntity::class, ['id' => 'mime_type_id']);
    }

    public function getImage(): ActiveQuery
    {
        return $this->hasOne(ImageEntity::class, ['file_id' => 'id']);
    }

    public function getLocationAlias(): string
    {
        return $this->location_alias;
    }

    public function getModuleName(): string
    {
        return $this->entityGroup->module_name;
    }

    public function getEntityName(): string
    {
        return $this->entityGroup->entity_name;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getSpecificEntityID(): int
    {
        return $this->specific_entity_id;
    }

    public function isImage(): bool
    {
        return $this->mimeType->type == 'image';
    }

    public function isThumbnailExist(int $width, int $height, int $quality = 85): bool
    {
        $file = $this->getThumbnailFullPath($width, $height, $quality);
        return file_exists($file);
    }

    public function getUrl(): string
    {
        if (self::FRONTEND_LOCATION_ALIAS === $this->location_alias) {
            $domain = $this->settings->getFrontendDomain();
        } else {
            $domain = $this->settings->getBackendDomain();
        }
        $moduleDirectory = $this->entityGroup->getDirectory();
        $fileDirectory   = $this->getDirectory();
        $fileName        = $this->name;

        return "{$domain}{$moduleDirectory}/{$fileDirectory}/{$fileName}";
    }

    public function getFileFullPath(): string
    {
        return self::getFullPathToFileDirectory($this) . '/' . $this->name;
    }

    public function getThumbnailDirectoryFullPath(int $width, int $height, int $quality = 85): string
    {
        return Yii::getAlias($this->getLocationAlias()) .
            '/web' .
            $this->getThumbnailWebPath($width, $height, $quality);
    }

    protected function getThumbnailWebPath(int $width, int $height, int $quality = 85): string
    {
        return '/image-cache' .
            "/{$width}x{$height}-{$quality}" .
            "/{$this->getModuleName()}" .
            "/{$this->getEntityName()}" .
            "{$this->getDirectory()}";
    }

    public function getThumbnailFullPath(int $width, int $height, int $quality = 85): string
    {
        return $directory = $this->getThumbnailDirectoryFullPath($width, $height, $quality) . '/' . $this->name;
    }

    public function getThumbnailWebUrl(int $width, int $height, int $quality = 85): string
    {
        if (self::FRONTEND_LOCATION_ALIAS === $this->location_alias) {
            $domain = $this->settings->getFrontendDomain();
        } else {
            $domain = $this->settings->getBackendDomain();
        }

        return $domain . $this->getThumbnailWebPath($width, $height, $quality) . "/{$this->name}";
    }

    public function getThumbnail(int $width, int $height, int $quality = 85): string
    {
        if ($this->isThumbnailExist($width, $height, $quality)) {
            return $this->getThumbnailWebUrl($width, $height, $quality);
        }
        $this->thumbnail($width, $height, $quality);
        return $this->getUrl();
    }

    protected function thumbnail(int $width, int $height, int $quality = 85): void
    {
        if ($this->isImage()) {
            $this->queue->push(new ThumbnailImagesJob([
                'fileId' => $this->id,
                'width' => $width,
                'height' => $height,
                'quality' => $quality,
            ]));
        }
    }

    public static function getFullPathToFileDirectory(FileInterface $file): string {
        return Yii::getAlias($file->getLocationAlias()) .
            '/web' .
            $file->getDirectory();
    }

    public static function getWebDirectory(FileInterface $file): string
    {
        $directory = md5((string) time());
        $directory = chunk_split($directory, 2, '/');
        $directory = substr($directory, 0, 8);
        return '/uploads/' .
            "{$file->getModuleName()}/" .
            "{$file->getEntityName()}/" .
            "{$directory}/" .
            "{$file->getSpecificEntityID()}";
    }

    public static function prepareFilename(string $fileName): string
    {
        $fileName = trim($fileName);
        $fileName = Inflector::transliterate($fileName);
        $fileName = mb_strtolower($fileName);
        $fileName = preg_replace("/[^a-z0-9.\s]/","-", $fileName);
        $fileName = preg_replace('/\s+/', '-', $fileName);
        $fileName = preg_replace('/[-]{2,}/', '-', $fileName);
        return trim($fileName, '-');
    }
}
