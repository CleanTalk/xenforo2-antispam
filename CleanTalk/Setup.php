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
	        $table->addColumn('status', 'tinyint', 1);
	    });

	    $this->schemaManager()->createTable('xf_cleantalk_sfw_logs', function(Create $table)
	    {
	        $table->addColumn('ip', 'varchar', 15);
	        $table->addColumn('all_entries', 'int', 11);
	        $table->addColumn('blocked_entries', 'int', 11);
	        $table->addColumn('entries_timestamp', 'int', 11);
	        $table->addPrimaryKey('ip');
	    });

	    // Adding a column to the post table to store the request id.
		$this->schemaManager()->alterTable('xf_post', function(\XF\Db\Schema\Alter $table)
		{
			$table->addColumn('ct_hash', 'varchar', 255)->setDefault('');
		});

	}

	public function upgrade16Step1()
	{
	    $this->schemaManager()->createTable('xf_cleantalk_sfw', function(Create $table)
	    {
	        $table->addColumn('network', 'int', 10);
	        $table->addColumn('mask', 'int', 10);
	    });

	    $this->schemaManager()->createTable('xf_cleantalk_sfw_logs', function(Create $table)
	    {
	        $table->addColumn('ip', 'varchar', 15);
	        $table->addColumn('all_entries', 'int', 11);
	        $table->addColumn('blocked_entries', 'int', 11);
	        $table->addColumn('entries_timestamp', 'int', 11);
	        $table->addPrimaryKey('ip');
	    });	
	}

	public function upgrade22Step1()
	{
		// Adding a column to the post table to store the request id.
		$this->schemaManager()->alterTable('xf_post', function(\XF\Db\Schema\Alter $table)
		{
			$table->addColumn('ct_hash', 'varchar', 255)->setDefault('');
		});
	}

	public function upgrade24Step1() {

	    $this->schemaManager()->createTable('xf_cleantalk_sfw', function(Create $table)
	    {
	        $table->addColumn('status', 'tinyint', 1);
	    });
	}

	public function uninstallStep1()
	{
	    $this->schemaManager()->dropTable('xf_cleantalk_sfw');
	    $this->schemaManager()->dropTable('xf_cleantalk_sfw_logs');
		$this->schemaManager()->alterTable('xf_post', function (\XF\Db\Schema\Alter $table) {
			$table->dropColumns(array('ct_hash'));
		});
	}	
}