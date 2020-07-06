<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\entities;

use Yii;
use yii\db\ActiveQuery;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;

/**
 * This is the model class for table "{{%dk_fm_mime_types}}".
 *
 * @property int $id
 * @property string $type
 * @property string $subtype
 *
 * @property FileEntity[] $files
 */
class MimeTypeEntity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dk_fm_mime_types}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'subtype'], 'required'],
            [['type'], 'string', 'max' => 45],
            [['subtype'], 'string', 'max' => 55],
            [['type', 'subtype'], 'unique', 'targetAttribute' => ['type', 'subtype']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t(FileManagerModule::TRANSLATE, 'ID'),
            'type' => Yii::t(FileManagerModule::TRANSLATE, 'Type'),
            'subtype' => Yii::t(FileManagerModule::TRANSLATE, 'Subtype'),
        ];
    }

    /**
     * Gets query for [[DkFmFiles]].
     *
     * @return ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(FileEntity::class, ['mime_type_id' => 'id']);
    }
}
