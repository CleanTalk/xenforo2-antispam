<?php

namespace CleanTalk;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;
use XF\Db\Schema\Drop;


class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	public function installStep1()
	{
	    $this->schemaManager()->createTable('xf_cleantalk_sfw', function(Create $table)
	    {
	        $table->addColumn('network', 'int', 10);
	        $table->addColumn('mask', 'int', 10);
	    });
	}

	public function installStep2()
	{
	    $this->schemaManager()->createTable('xf_cleantalk_sfw_logs', function(Create $table)
	    {
	        $table->addColumn('ip', 'varchar', 15);
	        $table->addColumn('all_entries', 'int', 11);
	        $table->addColumn('blocked_entries', 'int', 11);
	        $table->addColumn('entries_timestamp', 'int', 11);
	        $table->addPrimaryKey('ip');
	    });		
	}

	public function uninstallStep1()
	{
	    $this->schemaManager()->dropTable('xf_cleantalk_sfw');
	}

	public function uninstallStep2()
	{
	    $this->schemaManager()->dropTable('xf_cleantalk_sfw_logs');
	}	
}