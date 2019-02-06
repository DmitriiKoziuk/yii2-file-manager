<?php
namespace DmitriiKoziuk\yii2FileManager;

use Yii;
use yii\base\BootstrapInterface;
use DmitriiKoziuk\yii2ConfigManager\ConfigManager as ConfigModule;
use DmitriiKoziuk\yii2ConfigManager\services\ConfigService;
use DmitriiKoziuk\yii2ModuleManager\services\ModuleService;

final class Bootstrap implements BootstrapInterface
{
    /**
     * @param \yii\base\Application $app
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function bootstrap($app)
    {
        $container = Yii::$container;
        /** @var ConfigService $configService */
        $configService = $container->get(ConfigService::class);
        $app->setModule(FileManager::ID, [
            'class' => FileManager::class,
            'diContainer' => Yii::$container,
            'backendAppId' => $configService->getValue(
                ConfigModule::GENERAL_CONFIG_NAME,
                'backendAppId'
            ),
            'frontendDomainName' => $configService->getValue(
                ConfigModule::GENERAL_CONFIG_NAME,
                'frontendDomainName'
            ),
        ]);
        /** @var FileManager $module */
        $module = $app->getModule(FileManager::ID);
        /** @var ModuleService $moduleService */
        $moduleService = Yii::$container->get(ModuleService::class);
        $moduleService->registerModule($module);
    }
}