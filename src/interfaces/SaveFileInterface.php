<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\interfaces;

interface SaveFileInterface
{
    public function getEntityName(): string;

    public function getEntityId(): string;

    public function getSaveLocationAlias(): string;

    public function isRenameFile(): bool;

    public function getNewFileName(): string;

    public function isOptimizeFileName(): bool;

    public function isOverwriteExistFile(): bool;
}
