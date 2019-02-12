<?php
namespace DmitriiKoziuk\yii2FileManager;

use Yii;
use yii\base\BootstrapInterface;
use DmitriiKoziuk\yii2ConfigManager\ConfigManagerModule;
use DmitriiKoziuk\yii2ConfigManager\services\ConfigService;
use DmitriiKoziuk\yii2ModuleManager\services\ModuleInitService;

final class Bootstrap implements BootstrapInterface
{
    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app)
    {
        ModuleInitService::registerModule(FileManagerModule::class, function () {
            /** @var ConfigService $configService */
            $configService = Yii::$container->get(ConfigService::class);
            return [
                'class' => FileManagerModule::class,
                'diContainer' => Yii::$container,
                'backendAppId' => $configService->getValue(
                    ConfigManagerModule::GENERAL_CONFIG_NAME,
                    'backendAppId'
                ),
                'frontendDomainName' => $configService->getValue(
                    ConfigManagerModule::GENERAL_CONFIG_NAME,
                    'frontendDomainName'
                ),
            ];
        });
    }
}