<?php
	if (count(get_included_files()) == 1)
	{
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	if (array_key_exists('type', $_ELEMENT['attributes']))
	{
		$type = strtolower($_ELEMENT['attributes']['type']);
		unset ($_ELEMENT['attributes']['type']);
	}
	else
	{
		$type = 'text';
	}
	if ($type == 'file')
	{
		if (array_key_exists('readonly', $_ELEMENT['attributes']))
		{
			$_ELEMENT['attributes']['disabled'] = 'disabled';
			unset ($_ELEMENT['attributes']['readonly']);
		}
	}
	if ($type == 'checkbox' || $type == 'radio')
	{
		if (array_key_exists('value', $_ELEMENT['attributes']) && mb_strtolower($_ELEMENT['attributes']['value']) == 'true')
		{
			unset ($_ELEMENT['attributes']['value']);
			if (array_key_exists('readonly', $_ELEMENT['attributes']))
			{
				unset ($_ELEMENT['attributes']['readonly']);
				echo '<input type = "hidden" value = "on"'.PET_Utility::BuildAttributesString($_ELEMENT['attributes']).'>';
				unset ($_ELEMENT['attributes']['name']);
				echo '<input type = "'.$type.'" checked="checked" disabled="disabled"'.PET_Utility::BuildAttributesString($_ELEMENT['attributes']).'>';
			}
			else
			{
				echo '<input type = "'.$type.'" checked="checked"'.PET_Utility::BuildAttributesString($_ELEMENT['attributes']).'>';
			}
		}
		else
		{
			if (array_key_exists('readonly', $_ELEMENT['attributes']))
			{
				unset ($_ELEMENT['attributes']['readonly']);
				unset ($_ELEMENT['attributes']['name']);
				echo '<input type = "'.$type.'" disabled="disabled"'.PET_Utility::BuildAttributesString($_ELEMENT['attributes']).'>';
			}
			else
			{
				echo '<input type = "'.$type.'"'.PET_Utility::BuildAttributesString($_ELEMENT['attributes']).'>';
			}
		}
	}
	else if
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
		echo '<input type = "'.$type.'"'.PET_Utility::BuildAttributesString($_ELEMENT['attributes']).'>';
	}
	else
	{
		if ($type == 'html')
		{
			echo '<div'.PET_Utility::BuildAttributesString(Utility::ArraySkip($_ELEMENT['attributes'], array('type', 'value', 'readonly', 'name'))).'>';
			if (array_key_exists('value', $_ELEMENT['attributes']))
			{
				echo $_ELEMENT['attributes']['value'];
			}
			echo '</div>';
		}
		else if ($type == 'literal')
		{
			echo '<span'.PET_Utility::BuildAttributesString(Utility::ArraySkip($_ELEMENT['attributes'], array('type', 'value', 'readonly', 'name'))).'>';
			if (array_key_exists('value', $_ELEMENT['attributes']))
			{
				echo $_ELEMENT['attributes']['value'];
			}
			echo '</span>';
		}
		else if ($type == 'textarea')
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
			if (array_key_exists('readonly', $_ELEMENT['attributes']) && $_ELEMENT['attributes']['readonly'])
			{
				unset ($_ELEMENT['attributes']['readonly']);
				echo '<input type = "hidden"'.PET_Utility::BuildAttributesString($_ELEMENT['attributes']).'>';
				$_ELEMENT['attributes']['disabled'] = 'disabled';
			}
			echo PET_Utility::PETInvoke('select', Utility::ArraySkip($_ELEMENT['attributes'], 'readonly'));
		}
		else if ($type == 'entity-select')
		{
			if (!Database::CanConnect())
			{
				echo '<select'.PET_Utility::BuildAttributesString(Utility::ArraySkip($_ELEMENT['attributes'], array('entity', 'value', 'entity-field', 'readonly'))).'>';
				echo '</select>';
			}
			else if (array_key_exists('entity', $_ELEMENT['attributes']))
			{
				$entity = $_ELEMENT['attributes']['entity'];
				if (array_key_exists('value', $_ELEMENT['attributes']))
				{
					$value = PEN::Decode($_ELEMENT['attributes']['value']);
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
					if (array_key_exists('multiple', $_ELEMENT['attributes']) && array_key_exists('name', $_ELEMENT['attributes']))
					{
						$_ELEMENT['attributes']['name'] = String_Utility::EnsureEnd($_ELEMENT['attributes']['name'], '[]');
					}
					if (array_key_exists('readonly', $_ELEMENT['attributes']) && $_ELEMENT['attributes']['readonly'])
					{
						$_ELEMENT['attributes']['disabled'] = 'disabled';
					}
					echo '<select'.PET_Utility::BuildAttributesString(Utility::ArraySkip($_ELEMENT['attributes'], array('entity', 'value', 'entity-field', 'readonly'))).'>';
					if (array_key_exists('entity-field', $_ELEMENT['attributes']))
					{
						$entity_field = $_ELEMENT['attributes']['entity-field'];
						$entries = Database::Read($data['table'], array($primaryKey, $entity_field));
						foreach ($entries as $entry)
						{
							$primaryKeyValue = $entry[$primaryKey];
							echo '<option value="'.$primaryKeyValue.'"';
							if ((is_array($value) && in_array($primaryKeyValue, $value)) || ($value == $primaryKeyValue))
							{
								echo ' selected="selected"';
							}
							echo '>';
							echo $entry[$entity_field];
							echo '</option>';
						}
						echo '</select>';
					}
					else
					{
						$ids = Database::Read($data['table'], $primaryKey);
						foreach ($ids as $id)
						{
							$entry = call_user_func(array('EntityBase', 'Existing'), $id, $entity);
							$primaryKeyValue = $entry->$primaryKey;
							echo '<option value="'.$primaryKeyValue.'"';
							if (isset($value) && ((is_array($value) && in_array($primaryKeyValue, $value)) || ($value == $primaryKeyValue)))
							{
								echo ' selected="selected"';
							}
							echo '>';
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