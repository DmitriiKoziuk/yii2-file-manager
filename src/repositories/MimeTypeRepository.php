<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\repositories;

use DmitriiKoziuk\yii2Base\repositories\AbstractActiveRecordRepository;
use DmitriiKoziuk\yii2FileManager\entities\MimeTypeEntity;

class MimeTypeRepository extends AbstractActiveRecordRepository
{
    public function getMimeType(string $type, string $subtype): ?MimeTypeEntity
    {
        /** @var MimeTypeEntity|null $e */
        $e = MimeTypeEntity::find()->where([
            'type' => $type,
            'subtype' => $subtype,
        ])->one();
        return $e;
    }
}
