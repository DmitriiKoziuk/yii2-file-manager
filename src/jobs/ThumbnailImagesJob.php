<?php declare(strict_types=1);

namespace DmitriiKoziuk\yii2FileManager\jobs;

use Yii;
use Exception;
use yii\queue\Queue;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use DmitriiKoziuk\yii2FileManager\services\ThumbnailService;

class ThumbnailImagesJob extends BaseObject implements JobInterface
{
    /**
     * @var int
     */
    public $fileId;

    /**
     * @var int
     */
    public $width;

    /**
     * @var int
     */
    public $height;

    /**
     * @var int
     */
    public $quality;

    /**
     * @param Queue $queue
     * @return mixed|void
     * @throws Exception
     */
    public function execute($queue): void
    {
        /** @var ThumbnailService $thumbnailService */
        $thumbnailService = Yii::$container->get(ThumbnailService::class);
        $thumbnailService->thumbnail($this->fileId, $this->width, $this->height, $this->quality);
    }
}
