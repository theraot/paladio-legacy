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

	final class PHPMailer_Utility
	{
		private static $secure;
		private static $server;
		private static $port;
		private static $user;
		private static $key;
		private static $sender;
		private static $senderName;

		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static function GetMailer()
		{
			$mailer = new PHPMailer();
			$mailer->isSMTP();
			$mailer->SMTPAuth = true;
			$mailer->CharSet = 'UTF-8';
			$mailer->SMTPSecure = PHPMailer_Utility::$secure;
			$mailer->Host = PHPMailer_Utility::$sever;
			$mailer->Port = PHPMailer_Utility::$port;
			$mailer->Username = PHPMailer_Utility::$user;
			$mailer->Password = PHPMailer_Utility::$key;
			$mailer->From = PHPMailer_Utility::$sender;
			$mailer->FromName = PHPMailer_Utility::$senderName;
			return $mailer;
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function Configure(/*string*/ $secure, /*string*/ $server, /*string*/ $port, /*string*/ $user, /*string*/ $key, /*string*/ $sender, /*string*/ $senderName)
		{
			PHPMailer_Utility::$secure = $secure;
			PHPMailer_Utility::$server = $server;
			PHPMailer_Utility::$port = $port;
			PHPMailer_Utility::$user = $user;
			PHPMailer_Utility::$key = $key;
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
				//ONLY UTF-8
				$mailer->AltBody = Utility::Sanitize(str_replace('<br />', '\n', $body));
			}
			$mailer->AddAddress($recipient, Utility::Sanitize($recipientName));
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
	function PHPMailer_Utility_Configure()
	{
		PHPMailer_Utility::Configure
		(
			Configuration::Get('paladio-mail', 'secure', 'ssl'),
			Configuration::Get('paladio-mail', 'secure', 'ssl'),
			Configuration::Get('paladio-mail', 'server'),
			Configuration::Get('paladio-mail', 'port'),
			Configuration::Get('paladio-mail', 'user'),
			Configuration::Get('paladio-mail', 'key'),
			Configuration::Get('paladio-mail', 'sender'),
			Configuration::Get('paladio-mail', 'sender_name')
		);
	}
	Configuration::Callback('paladio-mail', 'PHPMailer_Utility_Configure');
?>