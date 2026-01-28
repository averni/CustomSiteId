<?php


namespace Piwik\Plugins\CustomSiteId\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Common;
use Piwik\Db;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Plugins\ExtraTools\Lib\Site;


class SetCustomSiteIdCommand extends ConsoleCommand {

    protected function configure()
    {

        $HelpText = 'The <info>%command.name%</info> command will set custom site id for an existing site.
<comment>Samples:</comment>
To run:
<info>%command.name% --name=Foo --custom-site-id=testsite</info>';
        $this->setHelp($HelpText);
        $this->setName('customsiteid:set');
        $this->setDescription('Set custom site id for an existing site');
        $this->setDefinition(
            [
                new InputOption(
                    'name',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Name for the site to update',
                    null
                ),
                new InputOption(
                    'custom-site-id',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'CustomSideId for the site',
                    null
                ),
                new InputOption(
                    'url',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'URL for the site',
                    null
                ),
                new InputOption(
                    'force',
                    null,
                    InputOption::VALUE_NONE,
                    'Update site even if it already has a custom site id',
                    null
                ),
            ]
        );
    }

    protected function getSiteIdByName($name)
    {
        $list = new Site(null);
        $sites = $list->list();

        foreach ($sites as $site) {
            if ($site['name'] == $name) {
                return $site['idsite'];
            }
        }
        return null;
    }

    protected function alreadyExists($siteId)
    {
        try {
            $stmt = Db::get()->query("SELECT * FROM `".Common::prefixTable('site_setting')."` WHERE plugin_name = ? and idsite = ?", array('CustomSiteId', $siteId));
            $row = $stmt->fetch();
            return (bool)$row;
        } catch (\Exception $e) {
            // If the table doesn't exist or query fails, treat as no existing setting
            return false;
        }
    }

    /**
     * Matomo 5+ compatibility: ConsoleCommand::execute() is final and delegates to doExecute().
     * Implement doExecute() instead of overriding execute() to avoid fatal errors.
     *
     * Return one of the class constants SUCCESS or FAILURE.
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        
        $siteName = $input->getOption('name');
        $customSiteId = $input->getOption('custom-site-id');
        $trimmedCustomSiteId = trim($customSiteId);
        $siteId = $this->getSiteIdByName($siteName);
        if (!$siteId) {
            $output->writeln("<error>Site not found</error>");
            return self::FAILURE;
        }

        $alreadyExists = $this->alreadyExists($siteId);
        if ($alreadyExists && !$input->getOption('force')) {
            $output->writeln("<error>Site already has a custom site id. Use --force to overwrite.</error>");
            return self::FAILURE;
        }

        $settings = new \Piwik\Plugins\CustomSiteId\MeasurableSettings($siteId);
        $settings->customSiteId->setValue($trimmedCustomSiteId);
        $settings->save();

        $output->writeln("<info>Custom site id '$trimmedCustomSiteId' set for site '$siteName'</info>");
        return self::SUCCESS;
    }
}
