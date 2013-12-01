<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('filesystem.lib.php');
		require_once(FileSystem::PreparePath(dirname(__FILE__)).'PHPMailer'.DIRECTORY_SEPARATOR.'class.phpmailer.php');
		require_once(FileSystem::PreparePath(dirname(__FILE__)).'PHPMailer'.DIRECTORY_SEPARATOR.'class.smtp.php');
	}
	//TODO: sending HTML needs testing

	final class PHPMailer_Utility
	{
		private static $secure;
		private static $server;
		private static $port;
		private static $user;
		private static $password;
		private static $sender;
		private static $senderName;

		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static function GetMailer()
		{
			$mailer = new PHPMailer();
			$mailer->Host = PHPMailer_Utility::$sever;
			$mailer->Port = PHPMailer_Utility::$port;
			$mailer->isSMTP();
			$mailer->SMTPAuth = true;
			$mailer->Username = PHPMailer_Utility::$user;
			$mailer->Password = PHPMailer_Utility::$password;
			$mailer->SMTPSecure = PHPMailer_Utility::$secure;
			
			$mailer->From = PHPMailer_Utility::$sender;
			$mailer->FromName = PHPMailer_Utility::$senderName;
			
			$mailer->CharSet = 'UTF-8';
			
			return $mailer;
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function Configure(/*string*/ $secure, /*string*/ $server, /*string*/ $port, /*string*/ $user, /*string*/ $password, /*string*/ $sender, /*string*/ $senderName)
		{
			PHPMailer_Utility::$secure = $secure;
			PHPMailer_Utility::$server = $server;
			PHPMailer_Utility::$port = $port;
			PHPMailer_Utility::$user = $user;
			PHPMailer_Utility::$password = $password;
			PHPMailer_Utility::$sender = $sender;
			PHPMailer_Utility::$senderName = $senderName;
		}

		public static function SendMail(/*string*/ $recipient, /*string*/ $recipientName, /*string*/ $subject, /*string*/ $body, /*bool*/ $isHtml = true, /*array*/ $files = null)
		{
			$mailer = PHPMailer_Utility::GetMailer();
			$mailer->Subject = $subject;
			$mailer->IsHTML($isHtml);
			$mailer->Body = $body;
			if ($isHtml)
			{
				//TODO
				$mailer->AltBody = Utility::Sanitize(str_replace('<br />', '\n', $body));
			}
			$mailer->AddAddress($recipient, Utility::Sanitize($recipientName, 'url'));
			if (!is_null($files))
			{
				foreach ($files as $file)
				{
					if (is_file($file))
					{
						$mailer->AddAttachment($file);
					}
				}
			}
			return $mailer->Send();
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}
	
	require_once('configuration.lib.php');
	Configuration::Callback
	(
		'paladio-mail',
		create_function
		(
			'',
			<<<'EOT'
				PHPMailer_Utility::Configure
				(
					Configuration::Get('paladio-mail', 'secure', 'ssl'),
					Configuration::Get('paladio-mail', 'secure', 'ssl'),
					Configuration::Get('paladio-mail', 'server'),
					Configuration::Get('paladio-mail', 'port'),
					Configuration::Get('paladio-mail', 'user'),
					Configuration::Get('paladio-mail', 'password'),
					Configuration::Get('paladio-mail', 'sender'),
					Configuration::Get('paladio-mail', 'sender_name')
				);
EOT
		)
	);
?>