<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\entities;

use Yii;
use yii\db\ActiveQuery;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;

/**
 * This is the model class for table "{{%dk_fm_entity_groups}}".
 *
 * @property int $id
 * @property string $module_name
 * @property string $entity_name
 *
 * @property FileEntity[] $files
 */
class GroupEntity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%dk_fm_entity_groups}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['module_name', 'entity_name'], 'required'],
            [['module_name'], 'string', 'max' => 45],
            [['entity_name'], 'string', 'max' => 55],
            [['module_name', 'entity_name'], 'unique', 'targetAttribute' => ['module_name', 'entity_name']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'module_name' => Yii::t(FileManagerModule::TRANSLATE, 'Module Name'),
            'entity_name' => Yii::t(FileManagerModule::TRANSLATE, 'Entity Name'),
        ];
    }

    /**
     * Gets query for [[DkFmFiles]].
     *
     * @return ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(FileEntity::class, ['entity_group_id' => 'id']);
    }
}
