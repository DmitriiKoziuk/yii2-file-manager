<?php
namespace DmitriiKoziuk\yii2FileManager\interfaces;

interface EntityFileInterface
{
    public function getEntityName(): string;

    public function getEntityId(): string;
}