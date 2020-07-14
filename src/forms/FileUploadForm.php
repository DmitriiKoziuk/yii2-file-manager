<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\forms;

use yii\base\Model;

class FileUploadForm extends Model
{
    public ?string $locationAlias = null;
    public ?string $moduleName = null;
    public ?string $entityName = null;
    public ?int $specificEntityId = null;

    public function rules()
    {
        return [
            [['locationAlias', 'moduleName', 'entityName', 'specificEntityId'], 'required'],
            [['moduleName'], 'string', 'max' => 45],
            [['entityName'], 'string', 'max' => 55],
            [['locationAlias'], 'string', 'max' => 25],
            [['specificEntityId'], 'integer']
        ];
    }

    public static function getName()
    {
        return str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
    }
}
