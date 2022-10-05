<?php


namespace Piwik\Plugins\CustomSiteId\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Common;
use Piwik\Db;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Piwik\Plugins\ExtraTools\Lib\Site;


class GetCustomSiteIdCommand extends ConsoleCommand {

    protected function configure()
    {

        $HelpText = 'The <info>%command.name%</info> command will get custom site id for an existing site.
<comment>Samples:</comment>
To run:
<info>%command.name% --custom-site-id=testsite</info>';
        $this->setHelp($HelpText);
        $this->setName('customsiteid:get');
        $this->setDescription('Get custom site id for an existing site');
        $this->setDefinition(
            [
                new InputOption(
                    'custom-site-id',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'CustomSideId for the site',
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

    protected function alreadyExists($customSiteId)
    {
        $stmt = Db::get()->query("SELECT * FROM `".Common::prefixTable('site_setting')."` WHERE plugin_name = ? and setting_name = ? and setting_value = ?", array('CustomSiteId', 'custom_site_id', $customSiteId));
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
        $customSiteId = $input->getOption('custom-site-id');
        $trimmedCustomSiteId = trim($customSiteId);

        $alreadyExists = $this->alreadyExists($customSiteId);
        
        if (!$alreadyExists) {
            $output->writeln("<error>Custom site id $customSiteId does not exist.</error>");
            return;
        }

        $stmt = Db::get()->query("SELECT idsite FROM `".Common::prefixTable('site_setting')."` WHERE plugin_name = ? and setting_name = ? and setting_value = ?", array('CustomSiteId', 'custom_site_id', $customSiteId));
        $row = $stmt->fetch();
        $output->writeln($row['idsite']);
        return;
    }
}