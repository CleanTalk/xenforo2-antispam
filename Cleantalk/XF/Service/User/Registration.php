<?php

namespace CleanTalk\XF\Service\User;
use CleanTalk\Cleantalk;
use CleanTalk\CleantalkRequest;
class Registration extends \XF\Service\AbstractService
{
		use \XF\Service\ValidateAndSavableTrait;

	/**
	 * @var \XF\Entity\User
	 */
	protected $user;

	protected $fieldMap = [
		'username' => 'username',
		'email' => 'email',
		'timezone' => 'timezone',
		'location' => 'Profile.location',
	];

	protected $logIp = true;

	protected $avatarUrl = null;

	protected $skipEmailConfirm = false;

	protected function setup()
	{
		$this->user = $this->app->repository('XF:User')->setupBaseUser();
	}

	public function getUser()
	{
		return $this->user;
	}

	public function setMapped(array $input)
	{
		foreach ($this->fieldMap AS $inputKey => $entityKey)
		{
			if (!isset($input[$inputKey]))
			{
				continue;
			}

			$value = $input[$inputKey];
			if (strpos($entityKey, '.'))
			{
				list($relation, $relationKey) = explode('.', $entityKey, 2);
				$this->user->{$relation}->{$relationKey} = $value;
			}
			else
			{
				$this->user->{$entityKey} = $value;
			}
		}

	}

	public function setPassword($password, $passwordConfirm = '', $doPasswordConfirmation = true)
	{
		if ($doPasswordConfirmation)
		{
			if ($password !== $passwordConfirm)
			{
				$this->user->error(\XF::phrase('passwords_did_not_match'));
				return false;
			}
		}

		if ($this->user->Auth->setPassword($password))
		{
			$this->user->Profile->password_date = \XF::$time;
		}
		return true;
	}

	public function setNoPassword()
	{
		$this->user->Auth->setNoPassword();
		$this->user->Profile->password_date = \XF::$time;
	}

	public function setDob($day, $month, $year)
	{
		return $this->user->Profile->setDob($day, $month, $year);
	}

	public function setCustomFields(array $values)
	{
		/** @var \XF\CustomField\Set $fieldSet */
		$fieldSet = $this->user->Profile->custom_fields;

		$fieldDefinition = $fieldSet->getDefinitionSet()
			->filterEditable($fieldSet, 'user')
			->filter('registration');

		$customFieldsShown = array_keys($fieldDefinition->getFieldDefinitions());

		if ($customFieldsShown)
		{
			$fieldSet->bulkSet($values, $customFieldsShown);
		}
	}

	public function setFromInput(array $input)
	{
		$this->setMapped($input);

		if (isset($input['password']))
		{
			$password = $input['password'];
			if (isset($input['password_confirm']))
			{
				$passwordConfirm = $input['password_confirm'];
				$doPasswordConfirmation = true;
			}
			else
			{
				$passwordConfirm = '';
				$doPasswordConfirmation = false;
			}

			$this->setPassword($password, $passwordConfirm, $doPasswordConfirmation);
		}

		if (isset($input['dob_day'], $input['dob_month'], $input['dob_year']))
		{
			$day = isset($input['dob_day']) ? $input['dob_day'] : 0;
			$month = isset($input['dob_month']) ? $input['dob_month'] : 0;
			$year = isset($input['dob_year']) ? $input['dob_year'] : 0;

			$this->setDob($day, $month, $year);
		}

		if (isset($input['custom_fields']))
		{
			$this->setCustomFields($input['custom_fields']);
		}
	}

	public function setAvatarUrl($url)
	{
		$this->avatarUrl = $url;
	}

	public function checkForSpam()
	{
		$user = $this->user;

		$userChecker = $this->app->spam()->userChecker();
		$userChecker->check($user);

		$decision = $userChecker->getFinalDecision();
        $refferrer = null;
        if (isset($_SERVER['HTTP_REFERER'])) {
            $refferrer = htmlspecialchars((string) $_SERVER['HTTP_REFERER']);
        }

        $user_agent = null;
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']);
        }
        $sender_info = array(
            'REFFERRER' => $refferrer,
            'post_url' => $refferrer,
            'USER_AGENT' => $user_agent
        );
        $js_on = 0;
        if (isset($_POST['ct_checkjs']) && $_POST['ct_checkjs'] == date("Y"))
            $js_on = 1;        
       	$session_values = $this->app['session.public']->get('registration');
        $sender_info = json_encode($sender_info);   
        $ct = new Cleantalk();
        $ct_request = new CleantalkRequest();
        $ct_request->auth_key = trim($this->app->options()->ct_apikey);
        $ct_request->sender_email = $user->email;
        $ct_request->sender_nickname = $user->username;
        $ct_request->sender_ip = $ct->cleantalk_get_real_ip();
        $ct_request->agent = 'xenforo2-10';
        $ct_request->js_on = $js_on;
        $ct_request->submit_time = time() - (int)$session_values['time'];
        $ct_request->sender_info = $sender_info;
        $ct->work_url = 'http://moderate.cleantalk.org';
        $ct->server_url = 'http://moderate.cleantalk.org';
        $ct_result = $ct->isAllowUser($ct_request);
        if ($ct_result->errno == 0 && $ct_result->allow == 0)
        {
 			$decision ='cleantalk_spam';  
 			$reason = $ct_result->comment;      	
        }
		switch ($decision)
		{
			case 'denied':
				$user->rejectUser(\XF::phrase('spam_prevention_registration_rejected'));
				break;

			case 'moderated':
				$user->user_state = 'moderated';
				break;
			case 'cleantalk_spam':
				$user->error($reason);
				break;
		}
	}

	public function skipEmailConfirmation($skip = true)
	{
		$this->skipEmailConfirm = $skip;
	}

	protected function _validate()
	{
		$this->finalSetup();

		$user = $this->user;
		$user->preSave();

		$this->applyExtraValidation();
		return $user->getErrors();
	}

	protected function finalSetup()
	{
		$user = $this->user;

		if (!$user->getErrors() && $user->email && !$this->avatarUrl)
		{
			if ($this->app->options()->gravatarEnable && $this->app->validator('Gravatar')->isValid($user->email))
			{
				$user->gravatar = $user->email;
			}
		}

		$this->setInitialUserState();
	}

	protected function setInitialUserState()
	{
		$user = $this->user;
		$options = $this->app->options();

		if ($user->user_state != 'valid')
		{
			return; // We have likely already set the user state elsewhere, e.g. spam trigger
		}

		if ($options->registrationSetup['emailConfirmation'] && !$this->skipEmailConfirm)
		{
			$user->user_state = 'email_confirm';
		}
		else if ($options->registrationSetup['moderation'])
		{
			$user->user_state = 'moderated';
		}
		else
		{
			$this->user_state = 'valid';
		}
	}

	protected function applyExtraValidation()
	{
		$user = $this->user;
		$options = $this->app->options();
		$age = $user->Profile->getAge(true);

		if ($options->registrationSetup['requireDob'])
		{
			if (!$age)
			{
				// incomplete dob
				$user->error(\XF::phrase('please_enter_valid_date_of_birth'), 'dob');
			}
			else if ($options->registrationSetup['minimumAge'])
			{
				if ($age < intval($options->registrationSetup['minimumAge']))
				{
					$user->error(\XF::phrase('sorry_you_too_young_to_create_an_account'), 'dob');
				}
			}
		}

		if (!empty($options->registrationSetup['requireLocation']) && !$user->Profile->location)
		{
			$user->error(\XF::phrase('please_enter_valid_location'), 'location');
		}
	}

	protected function _save()
	{
		$user = $this->user;

		$user->save();

		$this->app->spam()->userChecker()->logSpamTrigger('user', $user->user_id);

		if ($this->logIp)
		{
			$ip = ($this->logIp === true ? $this->app->request()->getIp() : $this->logIp);
			$this->writeIpLog($ip);
		}

		$this->updateUserAchievements();
		$this->sendRegistrationContact();

		if ($this->avatarUrl)
		{
			$this->applyAvatarFromUrl($this->avatarUrl);
		}

		return $user;
	}

	protected function writeIpLog($ip)
	{
		$user = $this->user;

		/** @var \XF\Repository\IP $ipRepo */
		$ipRepo = $this->repository('XF:Ip');
		$ipRepo->logIp($user->user_id, $ip, 'user', $user->user_id, 'register');
	}

	protected function updateUserAchievements()
	{
		/** @var \XF\Repository\UserGroupPromotion $userGroupPromotionRepo */
		$userGroupPromotionRepo = $this->repository('XF:UserGroupPromotion');
		$userGroupPromotionRepo->updatePromotionsForUser($this->user);

		if ($this->app->options()->enableTrophies)
		{
			/** @var \XF\Repository\Trophy $trophyRepo */
			$trophyRepo = $this->repository('XF:Trophy');
			$trophyRepo->updateTrophiesForUser($this->user);
		}
	}

	protected function sendRegistrationContact()
	{
		$user = $this->user;

		if ($user->user_state == 'email_confirm')
		{
			/** @var \XF\Service\User\EmailConfirmation $emailConfirmation */
			$emailConfirmation = $this->service('XF:User\EmailConfirmation', $user);
			$emailConfirmation->triggerConfirmation();
		}
		else if ($user->user_state == 'valid')
		{
			/** @var \XF\Service\User\Welcome $userWelcome */
			$userWelcome = $this->service('XF:User\Welcome', $user);
			$userWelcome->send();
		}
	}

	public function applyAvatarFromUrl($url)
	{
		if (!$this->user->user_id)
		{
			throw new \LogicException("User is not saved yet");
		}

		$app = $this->app;

		$validator = $app->validator('Url');
		if (!$validator->isValid($url))
		{
			return false;
		}

		$tempFile = \XF\Util\File::getTempFile();
		if ($app->http()->reader()->getUntrusted($url, [], $tempFile))
		{
			/** @var \XF\Service\User\Avatar $avatarService */
			$avatarService = $this->service('XF:User\Avatar', $this->user);
			if (!$avatarService->setImage($tempFile))
			{
				return false;
			}
			return $avatarService->updateAvatar();
		}
		else
		{
			return false;
		}
	}

}