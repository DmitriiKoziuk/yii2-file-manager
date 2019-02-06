<?php
namespace DmitriiKoziuk\yii2FileManager\services;

use yii\data\ActiveDataProvider;
use DmitriiKoziuk\yii2FileManager\entities\File;
use DmitriiKoziuk\yii2FileManager\data\FileSearchParams;

class FileSearchService
{
    public function searchBy(FileSearchParams $params): ActiveDataProvider
    {
        $query = File::find();

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

        $query->andFilterWhere(['like', 'entity_name', $params->entity_name])
            ->andFilterWhere(['like', 'entity_id', $params->entity_id])
            ->andFilterWhere(['like', 'location_alias', $params->location_alias])
            ->andFilterWhere(['like', 'mime_type', $params->mime_type])
            ->andFilterWhere(['like', 'name', $params->name])
            ->andFilterWhere(['like', 'extension', $params->extension])
            ->andFilterWhere(['like', 'title', $params->title]);

        return $dataProvider;
    }
}