<?php

namespace PerfectCode\DeployEnv\Setup;

use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\Setup\Option;
use Magento\Framework\Setup\Option\TextConfigOption;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ConfigOptionsList implements ConfigOptionsListInterface
{
    /**
     * @const string
     */
    const INPUT_KEY_INSTALL_DATE = 'install-date';

    /**
     * @const string
     */
    const INPUT_KEY_MAGE_MODE = 'mage-mode';

    /**
     * @const string
     */
    const CONFIG_PATH_INSTALL_DATE = 'install/date';

    /**
     * @const string
     */
    const CONFIG_PATH_MAGE_MODE = 'MAGE_MODE';

    /**
     * @var ConfigOptionsListInterface[]
     */
    private $configOptionsCollection = [];

    /**
     * ConfigOptionsList constructor.
     * @param ConfigOptionsListInterface[] $configOptionsCollection
     */
    public function __construct(array $configOptionsCollection = [])
    {
        foreach ($configOptionsCollection as $configOptionsList) {
            $this->addConfigOptionsList($configOptionsList);
        }
    }

    /**
     * @param ConfigOptionsListInterface $configOptionsList
     * @return $this
     */
    public function addConfigOptionsList(ConfigOptionsListInterface $configOptionsList)
    {
        $this->configOptionsCollection[] = $configOptionsList;

        return $this;
    }

    /**
     * Gets a list of input options so that user can provide required
     * information that will be used in deployment config file
     *
     * @return Option\AbstractConfigOption[]
     */
    public function getOptions()
    {
        $options = [
            new TextConfigOption(
                self::INPUT_KEY_INSTALL_DATE,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_INSTALL_DATE,
                'Installation Date',
                date('r')
            ),
            new TextConfigOption(
                self::INPUT_KEY_MAGE_MODE,
                TextConfigOption::FRONTEND_WIZARD_TEXT,
                self::CONFIG_PATH_MAGE_MODE,
                'Setup Run Mode',
                \Magento\Framework\App\State::MODE_DEFAULT
            ),
        ];

        foreach ($this->configOptionsCollection as $configOptionsList) {
            $options = array_merge($options, $configOptionsList->getOptions());
        }

        return $options;
    }

    /**
     * @param array $options
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     *
     * @return \Magento\Framework\Config\Data\ConfigData[]
     */
    public function createConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);

        $optional = [
            self::INPUT_KEY_INSTALL_DATE => self::CONFIG_PATH_INSTALL_DATE,
            self::INPUT_KEY_MAGE_MODE    => self::CONFIG_PATH_MAGE_MODE,
        ];

        foreach ($optional as $inputKey => $key) {
            if (isset($options[$inputKey])) {
                $configData->set(
                    $key, $options[$inputKey]
                );
            }
        }

        $configData = [$configData];

        foreach ($this->configOptionsCollection as $configOptionsList) {
            $configData[] = $configOptionsList->createConfig($options, $deploymentConfig);
        }

        return $configData;
    }

    /**
     * Validates user input option values and returns error messages
     *
     * @param array $options
     * @param DeploymentConfig $deploymentConfig
     *
     * @return string[]
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        $errors = [];

        foreach ($this->configOptionsCollection as $configOptionsList) {
            $errors = array_merge($errors, $configOptionsList->validate($options, $deploymentConfig));
        }

        return $errors;
    }
}
