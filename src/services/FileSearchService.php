<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\services;

use yii\data\ActiveDataProvider;
use DmitriiKoziuk\yii2FileManager\entities\FileEntity;
use DmitriiKoziuk\yii2FileManager\data\FileSearchForm;

class FileSearchService
{
    public function searchBy(FileSearchForm $params): ActiveDataProvider
    {
        $query = FileEntity::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        if (!$params->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $params->id,
            'size' => $params->size,
            'sort' => $params->sort,
            'created_at' => $params->created_at,
            'updated_at' => $params->updated_at,
        ]);

        return $dataProvider;
    }
}
