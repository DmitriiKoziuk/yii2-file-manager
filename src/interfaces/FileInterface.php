<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\interfaces;

interface FileInterface
{
    public function getLocationAlias(): string;
    public function getModuleName(): string;
    public function getEntityName(): string;
    public function getDirectory(): string;
    public function getSpecificEntityID(): int;
}
