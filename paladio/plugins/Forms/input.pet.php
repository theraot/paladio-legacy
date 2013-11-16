<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	if (array_key_exists('type', $_ELEMENT['attributes']))
	{
		$type = $_ELEMENT['attributes']['type'];
	}
	else
	{
		$type = 'text';
	}
	if
	(
		in_array
		(
			$type,
			array
			(
				'hidden',
				'text',
				'search',
				'tel',
				'url',
				'email',
				'password',
				'datetime',
				'date',
				'month',
				'week',
				'time',
				'datetime-local',
				'number',
				'range',
				'color',
				'checkbox',
				'radio',
				'file',
				'submit',
				'reset',
				'button'
			)
		)
	)
	{
		echo '<input type="'.$type.'" '.PET_Utility::BuildAttributesString(Utility::ArraySkip($_ELEMENT['attributes'], 'type')).'>';
	}
	else
	{
		if ($type == 'textarea')
		{
			echo '<textarea'.PET_Utility::BuildAttributesString(Utility::ArraySkip($_ELEMENT['attributes'], array('type', 'value'))).'>';
			if (array_key_exists('value', $_ELEMENT['attributes']))
			{
				echo $_ELEMENT['attributes']['value'];
			}
			echo '</textarea>';
		}
		else if ($type == 'select')
		{
			echo '<@select'.PET_Utility::BuildAttributesString(Utility::ArraySkip($_ELEMENT['attributes'], array('type'))).'/>';
		}
		else if ($type == 'entity-select')
		{
			if (array_key_exists('entity', $_ELEMENT['attributes']))
			{
				$entity = $_ELEMENT['attributes']['entity'];
				if (array_key_exists('value', $_ELEMENT['attributes']))
				{
					$value = $_ELEMENT['attributes']['value'];
				}
				$data = Entity::GetEntityData($entity);
				if (is_string($data['primaryKey']))
				{
					$primaryKey = $data['primaryKey'];
				}
				else if (is_array($data['primaryKey']) && count($data['primaryKey']) == 1)
				{
					$primaryKey = $data['primaryKey'][0];
				}
				if (isset($primaryKey))
				{
					echo '<select'.PET_Utility::BuildAttributesString(Utility::ArrayTake($_ELEMENT['attributes'], array('id', 'class', 'name'))).'>';
					if (array_key_exists('entity-value', $_ELEMENT['attributes']))
					{
						$value = $_ELEMENT['attributes']['entity-value'];
						$entries = Database::Read($data['table'], array($primaryKey, $value));
						foreach ($entries as $entry)
						{
							$primaryKeyValue = $entry[$primaryKey];
							echo '<option value="'.$primaryKeyValue.'"';
							if (isset($value) && $value == $primaryKeyValue)
							{
								echo ' selected="selected"';
							}
							echo '">';
							echo $entry[$value];
							echo '</option>';
						}
						echo '</select>';
					}
					else
					{
						$ids = Database::Read($data['table'], $primaryKey);
						foreach ($ids as $id)
						{
							$entry = $entity::Existing($id);
							$primaryKeyValue = $entry->$primaryKey;
							echo '<option value="'.$primaryKeyValue.'"';
							if (isset($value) && $value == $primaryKeyValue)
							{
								echo ' selected="selected"';
							}
							echo '">';
							echo $entry;
							echo '</option>';
						}
					}
					echo '</select>';
				}
			}
		}
	}
?>