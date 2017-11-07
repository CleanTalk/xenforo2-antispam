<?php

namespace CleanTalk;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;
	/**
	 * ----------------
	 *   INSTALLATION
	 * ----------------
	 */

	/* CREATE xf_kl_em_fonts */
	public function installStep1()
	{

	}
	/**
	 * ----------------
	 *     UPGRADES
	 * ----------------
	 */

	/* 1.0.0 Beta 2*/
	/* CREATE xf_kl_em_templates */
	public function upgrade10000Step1() 
	{

	}

	/**
	 * ----------------
	 *  UNINSTALLATION
	 * ----------------
	 */
	
	/* DROP xf_kl_em_fonts */
	public function uninstallStep1() 
	{

	}
}