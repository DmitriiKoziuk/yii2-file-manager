<?php

namespace DmitriiKoziuk\yii2FileManager\entities;

use DmitriiKoziuk\yii2FileManager\entities\File;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%dk_images}}".
 *
 * @property int    $file_id
 * @property int    $width
 * @property int    $height
 * @property string $alt
 *
 * @property File $file
 */
class Image extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dk_images}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['width', 'height'], 'required'],
            [['width', 'height'], 'integer'],
            [['alt'], 'string', 'max' => 255],
            [['alt'], 'default', 'value' => null],
            [
                ['file_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => File::class,
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
            'file_id' => Yii::t('dkFileManager', 'File ID'),
            'width'   => Yii::t('dkFileManager', 'Width'),
            'height'  => Yii::t('dkFileManager', 'Height'),
            'alt'     => Yii::t('dkFileManager', 'Alt'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(File::class, ['id' => 'file_id']);
    }
}
