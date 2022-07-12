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
        $stmt = Db::get()->query("SELECT * FROM `".Common::prefixTable('site_setting')."` WHERE plugin_name = ? and idsite_setting = ?", array('CustomSiteId', $siteId));
        $row = $stmt->fetch();
        if ($row) {
            return true;
        }
        return false;
    }

    /**
     * Execute the command like: ./console customsiteid:set
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $siteName = $input->getOption('name');
        $customSiteId = $input->getOption('custom-site-id');
        $trimmedCustomSiteId = trim($customSiteId);
        $siteId = $this->getSiteIdByName($siteName);
        if (!$siteId) {
            $output->writeln("<error>Site not found</error>");
            return;
        }
        
        $alreadyExists = $this->alreadyExists($siteId);
        
        if ($alreadyExists) {
            if (!$input->getOption('force')) {
                $output->writeln("<error>Custom site id already exists</error>");
                return;
            }
        }
        $output->writeln("<info>Setting custom site id to $customSiteId for site id $siteId</info>");

        $settingsTableName = Common::prefixTable('site_setting');

        try {
            if ($alreadyExists) {
                Db::query("UPDATE `$settingsTableName` SET setting_value = ? WHERE plugin_name = ? and setting_name = ? and idsite = ?", array($trimmedCustomSiteId, 'CustomSiteId', 'custom_site_id', $siteId));
            } else {
                Db::query("INSERT INTO `$settingsTableName` (idsite, plugin_name, setting_name, setting_value, json_encoded) VALUES (?, ?, ?, ?, 0)", array($siteId, 'CustomSiteId', 'custom_site_id',  $trimmedCustomSiteId));
            }
            $output->writeln("<info>Custom site id added</info>");
            return true;
        } catch (\Exception $e) {
            $output->writeln("<error>Error: " . $e->getMessage() . "</error>");
            return false;
        }
    }
}