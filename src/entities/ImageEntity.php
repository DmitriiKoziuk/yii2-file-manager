<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\entities;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;

/**
 * This is the model class for table "{{%dk_fm_images}}".
 *
 * @property int $file_id
 * @property int $width
 * @property int $height
 * @property int $orientation
 *
 * @property FileEntity $file
 */
class ImageEntity extends ActiveRecord
{
    const ORIENTATION_SQUARE = 'square';
    const ORIENTATION_PORTRAIT = 'portrait';
    const ORIENTATION_LANDSCAPE = 'landscape';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dk_fm_images}}';
    }

    public function rules()
    {
        return [
            [['file_id', 'width', 'height', 'orientation'], 'required'],
            [['file_id', 'width', 'height'], 'integer'],
            [['orientation'], 'string'],
            [['orientation'], 'in', 'range' => self::getOrientations()],
            [
                ['file_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => FileEntity::class,
                'targetAttribute' => ['file_id' => 'id']
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'file_id' => Yii::t(FileManagerModule::TRANSLATE, 'File ID'),
            'width' => Yii::t(FileManagerModule::TRANSLATE, 'Width'),
            'height' => Yii::t(FileManagerModule::TRANSLATE, 'Height'),
            'orientation' => Yii::t(FileManagerModule::TRANSLATE, 'Orientation'),
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(FileEntity::class, ['id' => 'file_id']);
    }

    public static function getOrientations()
    {
        return [
            self::ORIENTATION_SQUARE => self::ORIENTATION_SQUARE,
            self::ORIENTATION_PORTRAIT => self::ORIENTATION_PORTRAIT,
            self::ORIENTATION_LANDSCAPE => self::ORIENTATION_LANDSCAPE,
        ];
    }
}
