<?php
namespace DmitriiKoziuk\yii2FileManager\helpers;

use yii\helpers\Url;
use DmitriiKoziuk\yii2FileManager\FileManagerModule;
use DmitriiKoziuk\yii2FileManager\entities\File;

class FileWebHelper
{
    private $_frontendDomainName;
    private $_uploadFileFolder;

    public function __construct(string $frontendDomainName, string $uploadFileFolder)
    {
        $this->_frontendDomainName = $frontendDomainName;
        $this->_uploadFileFolder = $uploadFileFolder;
    }

    public function getFileFullWebPath(File $file): string
    {
        if (File::FRONTEND_LOCATION_ALIAS == $file->location_alias) {
            return $this->_frontendDomainName . $this->_getFileWebPath($file);
        } else {
            return $this->getFileFullWebPath($file);
        }
    }

    private function _getFileWebPath(File $file): string
    {
        $path = $this->_uploadFileFolder . '/' .
            $file->entity_name . '/' .
            $file->entity_id . '/' .
            $file->name . '.' . $file->extension;
        $path = str_replace('/web', '', $path);
        return $path;
    }

    /**
     * @param File[] $files
     * @return array
     * @throws \Exception
     */
    public function getFileInputInitialPreview(array $files): array
    {
        $list = [];
        foreach ($files as $file) {
            $list[] = $this->getFileFullWebPath($file);
        }
        return $list;
    }

    /**
     * @param File[] $files
     * @return array
     */
    public function getFileInputInitialPreviewConfig(array $files): array
    {
        $list = [];
        foreach ($files as $key => $file) {
            $list[] = [
                'caption' => $file->name . '.' . $file->extension,
                'size'    => $file->size,
                'url'     => Url::to(['/'. FileManagerModule::ID .'/file/delete', 'id' => $file->id]),
                'key'     => $file->sort,
            ];
        }
        return $list;
    }
}