<?php
namespace DmitriiKoziuk\yii2FileManager\data;

use DmitriiKoziuk\yii2Base\data\Data;

class FileSearchParams extends Data
{
    public $id;
    public $entity_name;
    public $entity_id;
    public $location_alias;
    public $mime_type;
    public $name;
    public $extension;
    public $size;
    public $title;
    public $sort;
    public $created_at;
    public $updated_at;

    public function rules()
    {
        return [
            [['id', 'size', 'sort', 'created_at', 'updated_at'], 'integer'],
            [['entity_name', 'entity_id'], 'string', 'max' => 45],
            [['location_alias', 'mime_type'], 'string', 'max' => 25],
            [[ 'title'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 155],
            [['extension'], 'string', 'max' => 10],
        ];
    }
}