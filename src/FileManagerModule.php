<?php
namespace DmitriiKoziuk\yii2FileManager;

use yii\BaseYii;
use yii\di\Container;
use yii\web\UploadedFile;
use yii\web\Application as WebApp;
use yii\base\Application as BaseApp;
use DmitriiKoziuk\yii2ModuleManager\interfaces\ModuleInterface;
use DmitriiKoziuk\yii2FileManager\repositories\FileRepository;
use DmitriiKoziuk\yii2FileManager\services\FileActionService;
use DmitriiKoziuk\yii2FileManager\helpers\FileWebHelper;
use DmitriiKoziuk\yii2FileManager\helpers\FileHelper;

final class FileManagerModule extends \yii\base\Module implements ModuleInterface
{
    const ID = 'dk-file-manager';

    const TRANSLATE = self::ID;

    /**
     * @var Container
     */
    public $diContainer;

    /**
     * Overwrite this param if you backend app id is different from default.
     * @var string
     */
    public $backendAppId;

    /**
     * Domain name with protocol and without end slash.
     * Need for display image preview that load in @frontend location.
     * @var string
     */
    public $frontendDomainName;

    /**
     * @var string
     */
    public $uploadFilePath;

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function init()
    {
        /** @var BaseApp $app */
        $app = $this->module;
        $this->_initLocalProperties($app);
        $this->_registerTranslation($app);
        $this->_registerClassesToDIContainer($app);
    }

    public function getId(): string
    {
        return $this::ID;
    }

    public function getBackendMenuItems(): array
    {
        return ['label' => 'File manager', 'url' => ['/' . self::ID . '/file/index']];
    }

    /**
     * @param BaseApp $app
     * @throws \InvalidArgumentException
     */
    private function _initLocalProperties(BaseApp $app)
    {
        if ($app instanceof WebApp && $app->id == $this->backendAppId) {
            $this->controllerNamespace = __NAMESPACE__ . '\controllers\backend';
        }
        if (empty($this->uploadFilePath)) {
            $this->uploadFilePath = DIRECTORY_SEPARATOR .
                'web'. DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'files';
        }
        if (empty($this->backendAppId)) {
            throw new \InvalidArgumentException('Property backendAppId not set.');
        }
        if (empty($this->frontendDomainName)) {
            throw new \InvalidArgumentException('Frontend domain name not set.');
        }
    }

    private function _registerTranslation(BaseApp $app)
    {
        $app->i18n->translations[self::TRANSLATE] = [
            'class'          => 'yii\i18n\PhpMessageSource',
            'sourceLanguage' => 'en',
            'basePath'       => '@DmitriiKoziuk/yii2FileManager/messages',
        ];
    }

    /**
     * @param BaseApp $app
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    private function _registerClassesToDIContainer(BaseApp $app): void
    {
        $this->diContainer->setSingleton(FileHelper::class, function () {
            return new FileHelper(new BaseYii(), $this->uploadFilePath);
        });
        $this->diContainer->setSingleton(FileRepository::class, function () {
            return new FileRepository();
        });
        /** @var FileRepository $fileRepository */
        $fileRepository = $this->diContainer->get(FileRepository::class);
        $this->diContainer->setSingleton(FileActionService::class, function () use ($fileRepository, $app) {
            return new FileActionService(
                new BaseYii(),
                $this->uploadFilePath,
                new FileHelper(new BaseYii(), $this->uploadFilePath),
                new UploadedFile(),
                $fileRepository,
                $app->db
            );
        });
        $this->diContainer->setSingleton(FileWebHelper::class, function () {
           return new FileWebHelper(
               $this->frontendDomainName,
               $this->uploadFilePath
           );
        });
    }
}