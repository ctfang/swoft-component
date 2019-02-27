<?php declare(strict_types=1);

namespace Swoft;

use Swoft\Contract\ApplicationInterface;
use Swoft\Contract\SwoftInterface;
use Swoft\Helper\CLog;
use Swoft\Processor\AnnotationProcessor;
use Swoft\Processor\ApplicationProcessor;
use Swoft\Processor\BeanProcessor;
use Swoft\Processor\ConfigProcessor;
use Swoft\Processor\ConsoleProcessor;
use Swoft\Processor\EnvProcessor;
use Swoft\Processor\EventProcessor;
use Swoft\Processor\Processor;
use Swoft\Processor\ProcessorInterface;
use Swoft\Stdlib\Helper\ComposerHelper;

/**
 * Swoft application
 * @since 2.0
 */
class SwoftApplication implements SwoftInterface, ApplicationInterface
{
    /**
     * Swoft trait
     */
    use SwoftTrait;

    /**
     * Base path
     *
     * @var string
     */
    protected $basePath = '';

    /**
     * Application path
     *
     * @var string
     */
    protected $appPath = '@base/app';

    /**
     * Runtime path
     *
     * @var string
     */
    protected $runtimePath = '@base/runtime';

    /**
     * Config path
     *
     * @var string
     */
    protected $configPath = '@base/config';

    /**
     * Env file
     *
     * @var string
     */
    protected $envFile = '@base/.env';

    /**
     * Default bean file
     *
     * @var string
     */
    protected $beanFile = '@app/bean.php';

    /**
     * @var ApplicationProcessor
     */
    protected $processor;

    /**
     * Can disable processor class before handle.
     * eg.
     * [
     *  Swoft\Processor\ConsoleProcessor::class => 1,
     * ]
     *
     * @var array
     */
    protected $disabledProcessors = [];

    /**
     * Can disable AutoLoader class before handle.
     * eg.
     * [
     *  Swoft\Console\AutoLoader::class  => 1,
     * ]
     *
     * @var array
     */
    protected $disabledAutoLoaders = [];

    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->initCLogger();

        $processors = $this->processors();

        $this->processor = new ApplicationProcessor($this);
        $this->processor->addFirstProcessor(...$processors);

        // Set system alias
        $this->setSystemAlias();

        $this->init();
    }

    protected function init()
    {
        // do something ...
    }

    /**
     * Run application
     *
     * @throws Bean\Exception\ContainerException
     * @throws \ReflectionException
     */
    public function run(): void
    {
        if (!$this->beforeRun()) {
            return;
        }

        $this->processor->handle();

        // trigger a app init event
        \Swoft::trigger(SwoftEvent::APP_INIT_AFTER);
    }

    /**
     * @param string ...$classes
     */
    public function disableAutoLoader(string ...$classes)
    {
        foreach ($classes as $class) {
            $this->disabledAutoLoaders[$class] = 1;
        }
    }

    /**
     * @param string ...$classes
     */
    public function disableProcessor(string ...$classes)
    {
        foreach ($classes as $class) {
            $this->disabledProcessors[$class] = 1;
        }
    }

    /**
     * Add first processors
     *
     * @param Processor[] $processors
     *
     * @return bool
     */
    public function addFirstProcessor(Processor ...$processors): bool
    {
        return $this->processor->addFirstProcessor(...$processors);
    }

    /**
     * Add last processors
     *
     * @param Processor[] $processors
     *
     * @return true
     */
    public function addLastProcessor(Processor ...$processors): bool
    {
        return $this->processor->addLastProcessor(...$processors);
    }

    /**
     * Add processors
     *
     * @param int         $index
     * @param Processor[] $processors
     *
     * @return true
     */
    public function addProcessor(int $index, Processor ...$processors): bool
    {
        return $this->processor->addProcessor($index, ... $processors);
    }

    /**
     * @return ProcessorInterface[]
     */
    protected function processors(): array
    {
        return [
            new EnvProcessor($this),
            new AnnotationProcessor($this),
            new ConfigProcessor($this),
            new BeanProcessor($this),
            new EventProcessor($this),
            new ConsoleProcessor($this),
        ];
    }

    /**
     * @return array
     */
    public function getDisabledProcessors(): array
    {
        return $this->disabledProcessors;
    }

    /**
     * @return array
     */
    public function getDisabledAutoLoaders(): array
    {
        return $this->disabledAutoLoaders;
    }

    /**
     * @param string $beanFile
     */
    public function setBeanFile(string $beanFile): void
    {
        $this->beanFile = $beanFile;
    }

    /**
     * @return string
     */
    public function getBeanFile(): string
    {
        return $this->beanFile;
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        $basePath = ComposerHelper::getClassLoader()->findFile(static::class);
        $basePath = dirname($basePath, 2);

        return $basePath;
    }

    /**
     * @return string
     */
    public function getAppPath(): string
    {
        return $this->appPath;
    }

    /**
     * @return string
     */
    public function getRuntimePath(): string
    {
        return $this->runtimePath;
    }

    /**
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * @param string $envFile
     */
    public function setEnvFile(string $envFile): void
    {
        $this->envFile = $envFile;
    }

    /**
     * @param string $appPath
     */
    public function setAppPath(string $appPath): void
    {
        $this->appPath = $appPath;

        \Swoft::setAlias('@app', $appPath);
    }

    /**
     * @param string $configPath
     */
    public function setConfigPath(string $configPath): void
    {
        $this->configPath = $configPath;

        \Swoft::setAlias('@config', $configPath);
    }

    /**
     * @param string $basePath
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;

        \Swoft::setAlias('@base', $basePath);
    }

    /**
     * @param string $runtimePath
     */
    public function setRuntimePath(string $runtimePath): void
    {
        $this->runtimePath = $runtimePath;

        \Swoft::setAlias('@config', $runtimePath);
    }

    /**
     * Get console logger config
     *
     * @return array
     */
    public function getCLoggerConfig(): array
    {
        return [
            'name'    => 'swoft',
            'enable'  => true,
            'output'  => true,
            'levels'  => [],
            'logFile' => ''
        ];
    }

    /**
     * Init console logger
     */
    private function initCLogger(): void
    {
        // Console logger config
        $config = $this->getCLoggerConfig();

        // Init console log
        CLog::init($config);
    }

    /**
     * Set base path
     */
    private function setSystemAlias(): void
    {
        $basePath    = $this->getBasePath();
        $appPath     = $this->getAppPath();
        $configPath  = $this->getConfigPath();
        $runtimePath = $this->getRuntimePath();

        \Swoft::setAlias('@base', $basePath);
        \Swoft::setAlias('@app', $appPath);
        \Swoft::setAlias('@config', $configPath);
        \Swoft::setAlias('@runtime', $runtimePath);

        CLog::info('set alias @base=%s', $basePath);
        CLog::info('set alias @app=%s', $appPath);
        CLog::info('set alias @config=%s', $configPath);
        CLog::info('set alias @runtime=%s', $runtimePath);
    }
}
