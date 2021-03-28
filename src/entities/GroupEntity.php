<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\entities;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;

/**
 * @property int         $id
 * @property string      $module_name
 * @property string      $entity_name
 * @property string|null $files_directory
 *
 * @property FileEntity[] $files
 */
class GroupEntity extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%dk_fm_entity_groups}}';
    }

    public function rules(): array
    {
        return [
            [['module_name', 'entity_name'], 'required'],
            [['module_name'], 'string', 'max' => 45],
            [['entity_name'], 'string', 'max' => 55],
            [['module_name', 'entity_name'], 'unique', 'targetAttribute' => ['module_name', 'entity_name']],
            [['files_directory'], 'string', 'max' => 55],
            [['files_directory'], 'default', 'value' => null],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => Yii::t(FileManagerModule::TRANSLATE, 'ID'),
            'module_name'     => Yii::t(FileManagerModule::TRANSLATE, 'Module Name'),
            'entity_name'     => Yii::t(FileManagerModule::TRANSLATE, 'Entity Name'),
            'files_directory' => Yii::t(FileManagerModule::TRANSLATE, 'Files Directory'),
        ];
    }

    public function getFiles(): ActiveQuery
    {
        return $this->hasMany(FileEntity::class, ['entity_group_id' => 'id']);
    }

    public function getDirectory(): string
    {
        if (is_null($this->files_directory)) {
            $directory = '/' . $this->module_name . '/' . $this->entity_name;
        } else {
            $directory = $this->files_directory;
        }
        return rtrim($directory, '/');
    }
}
