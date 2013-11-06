<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	else
	{
		require_once('filesystem.lib.php');
		require_once(FileSystem::PreparePath(dirname(__FILE__)).'addendum'.DIRECTORY_SEPARATOR.'annotations.php');
	}

	final class Addendum_Utility
	{
		//------------------------------------------------------------
		// Private (Class)
		//------------------------------------------------------------

		private static function ProcessAnnotation(/*annotation*/ $annotation, /*mixed*/ $field)
		{
			if (is_string($field))
			{
				return $annotation->$field;
			}
			else if (is_array($field))
			{
				$result = array();
				foreach($field as $item)
				{
					$result[$item] = $annotation->$item;
				}
				return $result;
			}
		}

		//------------------------------------------------------------
		// Public (Class)
		//------------------------------------------------------------

		public static function ReadAnnotation(/*string*/ $object, /*string*/ $annotationName, /*mixed*/ $field)
		{
			$reflection = new ReflectionAnnotatedClass($object);
			if ($reflection->hasAnnotation($annotationName))
			{
				$annotation = $reflection->getAnnotation($annotationName);
				return Addendum_Utility::ProcessAnnotation($annotation, $field);
			}
			else
			{
				return null;
			}
		}

		public static function ReadAnnotations(/*string*/ $object, /*string*/ $annotationName, /*mixed*/ $field)
		{
			$reflection = new ReflectionAnnotatedClass($object);
			$result = array();
			if ($reflection->hasAnnotation($annotationName))
			{
				$annotations = $reflection->getAllAnnotations($annotationName);
				foreach ($annotations as $annotation)
				{
					$result[] = Addendum_Utility::ProcessAnnotation($annotation, $field);
				}
			}
			return $result;
		}

		//------------------------------------------------------------
		// Public (Constructors)
		//------------------------------------------------------------

		public function __construct()
		{
			throw new Exception('Creating instances of '.__CLASS__.' is forbidden');
		}
	}
?>