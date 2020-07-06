<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\entities;

use Yii;
use yii\db\ActiveQuery;
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
class ImageEntity extends \yii\db\ActiveRecord
{
    const ORIENTATION_SQUARE = 0;
    const ORIENTATION_LANDSCAPE = 1;
    const ORIENTATION_PORTRAIT = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dk_fm_images}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['file_id', 'width', 'height', 'orientation'], 'required'],
            [['file_id', 'width', 'height', 'orientation'], 'integer'],
            [
                ['file_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => FileEntity::class,
                'targetAttribute' => ['file_id' => 'id']
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
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
     * Gets query for [[File]].
     *
     * @return ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(FileEntity::class, ['id' => 'file_id']);
    }
}
